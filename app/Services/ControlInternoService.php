<?php

namespace App\Services;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Responsible;
use App\Models\Transaction;
use App\Repositories\Contracts\ControlInternoRepositoryInterface;
use Carbon\Carbon;

/**
 * Datos de la tarjeta de control interno (kardex mensual) de leche y hojuela:
 * ingresos del mes y salidas por vuelta de reparto.
 */
class ControlInternoService
{
    public const PRODUCTS = [
        'milk' => [
            'name' => 'LECHE EVAPORADA ENTERA POR 410 GR',
            'card_name' => 'LECHE EVAPORADA ENTERA',
            'unit' => 'TARROS',
            'keywords' => ['LECHE'],
        ],
        'oat' => [
            'name' => 'HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES',
            'card_name' => 'HOJUELAS DE QUINUA AVENA',
            'unit' => 'KG',
            'keywords' => ['HOJUELA', 'AVENA'],
        ],
    ];

    private const MONTHS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    public function __construct(
        private ControlInternoRepositoryInterface $control,
        private ReparticionService $reparticionService,
    ) {
    }

    /** Filas precargadas de la tarjeta: ingresos del mes y salidas por PECOSA. */
    public function cardData(string $kind, int $year, int $month): array
    {
        $detailIds = $this->control->detailProductIdsByKeywords(self::PRODUCTS[$kind]['keywords']);

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $storekeeper = $this->activeStorekeeperName();

        $ingresos = $this->control->ingresosBetween($detailIds, $monthStart, $monthEnd)
            ->map(fn (Transaction $t) => [
                'date' => $t->transaction_date?->toDateString(),
                'time' => $t->created_at?->format('H:i') ?? '',
                'quantity' => (float) $t->quantity,
                'expiry' => $t->detailProduct?->end_date?->toDateString(),
                'document' => $t->document_number,
            ]);

        if ($ingresos->isEmpty()) {
            // Lotes migrados antes del módulo de movimientos: no tienen transacción.
            $ingresos = $this->control->lotsStartingBetween($detailIds, $monthStart, $monthEnd)
                ->map(fn (DetailProduct $d) => [
                    'date' => $d->start_date?->toDateString(),
                    'time' => '',
                    'quantity' => (float) $d->quantity,
                    'expiry' => $d->end_date?->toDateString(),
                    'document' => null,
                ]);
        }

        $firstPecosa = $this->control->pecosaDetailsForPeriod($detailIds, $year, $month)
            ->map(fn (DetailPecosa $d) => $d->pecosa?->delivery_date)
            ->filter()
            ->sort()
            ->first();

        $salidas = $this->vueltaTotals($kind, $year, $month);

        return [
            'product' => $kind,
            'product_name' => self::PRODUCTS[$kind]['card_name'],
            'month' => self::MONTHS[$month],
            'ingresos' => $ingresos->values(),
            'responsables' => $this->responsibleNames(),
            'rows' => $this->buildCardRows($ingresos->values()->all(), $salidas, $firstPecosa, $storekeeper),
        ];
    }

    /** Total de cada vuelta de reparto del mes para el producto (tarros o kg). */
    private function vueltaTotals(string $kind, int $year, int $month): array
    {
        $racion = $this->reparticionService->getActiveRacion($year, $month);
        if (! $racion) {
            return [];
        }

        $key = $kind === 'milk' ? 'total_leche' : 'total_hojuelas';

        return collect($this->reparticionService->buildReport($racion, $year, $month)['vueltas'])
            ->map(fn ($vuelta) => ['vuelta' => $vuelta['vuelta'], 'quantity' => (float) $vuelta[$key]])
            ->filter(fn ($vuelta) => $vuelta['quantity'] > 0)
            ->values()
            ->all();
    }

    /** Nombres de todas las personas registradas como responsables. */
    private function responsibleNames(): array
    {
        return $this->control->responsibles()
            ->map(fn (Responsible $r) => $r->person
                ? trim(collect([$r->person->names, $r->person->father_lastname, $r->person->mother_lastname])->filter()->implode(' '))
                : '')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function buildCardRows(array $ingresos, array $salidas, $baseDate, string $storekeeper): array
    {
        $rows = [];
        foreach ($ingresos as $ingreso) {
            $rows[] = [
                'date' => $ingreso['date'],
                'time' => $ingreso['time'],
                'in' => $ingreso['quantity'],
                'out' => null,
                'by' => $storekeeper,
            ];
        }

        // Una salida por vuelta, en dos jornadas: primera mitad de las vueltas
        // el día de la primera PECOSA, segunda mitad al día siguiente.
        $half = (int) ceil(count($salidas) / 2);
        foreach ($salidas as $index => $vuelta) {
            $day = $baseDate ? Carbon::parse($baseDate)->addDays($index < $half ? 0 : 1) : null;
            $rows[] = [
                'date' => $day?->toDateString(),
                'time' => '',
                'in' => null,
                'out' => $vuelta['quantity'],
                'by' => $storekeeper,
                'vuelta' => $vuelta['vuelta'],
            ];
        }

        return $rows;
    }

    private function activeStorekeeperName(): string
    {
        $person = $this->control->activeStorekeeper();

        return $person ? trim(collect([$person->names, $person->father_lastname])->filter()->implode(' ')) : '';
    }
}
