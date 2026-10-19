<?php

namespace App\Http\Controllers;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Pecosa;
use App\Models\Responsible;
use App\Models\Transaction;
use App\Services\PDFService;
use App\Services\ReparticionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Ficha de Control Interno (ingreso) y Tarjeta de Control Interno (kardex
 * mensual) de leche y hojuela. Los datos se editan en el modal y llegan al PDF
 * como un payload JSON, de modo que lo impreso es exactamente lo revisado.
 */
class ControlInternoController extends Controller
{
    private const PRODUCTS = [
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

    public function __construct(private PDFService $pdfService, private ReparticionService $reparticionService)
    {
    }

    /** Filas precargadas de la tarjeta: ingresos del mes y salidas por PECOSA. */
    public function tarjetaDatos(Request $request)
    {
        $validated = $request->validate([
            'product' => 'required|in:milk,oat',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $kind = $validated['product'];
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $detailIds = $this->detailProductIds($kind);

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $storekeeper = $this->activeStorekeeperName();

        $ingresos = Transaction::query()
            ->whereIn('detail_product_id', $detailIds)
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->where('quantity', '>', 0)
            ->whereHas('typeTransaction', fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', ['ingreso']))
            ->with('detailProduct:id,start_date,end_date')
            ->orderBy('transaction_date')->orderBy('id')
            ->get()
            ->map(fn (Transaction $t) => [
                'date' => $t->transaction_date?->toDateString(),
                'time' => $t->created_at?->format('H:i') ?? '',
                'quantity' => (float) $t->quantity,
                'expiry' => $t->detailProduct?->end_date?->toDateString(),
                'document' => $t->document_number,
            ]);

        if ($ingresos->isEmpty()) {
            // Lotes migrados antes del módulo de movimientos: no tienen transacción.
            $ingresos = DetailProduct::query()
                ->whereIn('id', $detailIds)
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->where('quantity', '>', 0)
                ->orderBy('start_date')->orderBy('id')
                ->get()
                ->map(fn (DetailProduct $d) => [
                    'date' => $d->start_date?->toDateString(),
                    'time' => '',
                    'quantity' => (float) $d->quantity,
                    'expiry' => $d->end_date?->toDateString(),
                    'document' => null,
                ]);
        }

        $firstPecosa = DetailPecosa::query()
            ->whereIn('detail_product_id', $detailIds)
            ->whereHas('pecosa', fn ($q) => $q->forDeliveryPeriod($year, $month))
            ->with('pecosa:id,delivery_date')
            ->get()
            ->map(fn (DetailPecosa $d) => $d->pecosa?->delivery_date)
            ->filter()
            ->sort()
            ->first();

        $salidas = $this->vueltaTotals($kind, $year, $month);

        return response()->json([
            'product' => $kind,
            'product_name' => self::PRODUCTS[$kind]['card_name'],
            'month' => self::MONTHS[$month],
            'ingresos' => $ingresos->values(),
            'responsables' => $this->responsibleNames(),
            'rows' => $this->buildCardRows($ingresos->values()->all(), $salidas, $firstPecosa, $storekeeper),
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    /** PDF de la ficha de control interno de ingreso (leche u hojuela). */
    public function fichaPdf(Request $request)
    {
        $kind = $this->kindFrom($request);
        $data = $this->payload($request);

        $normalize = fn ($list) => collect($list ?? [])->map(fn ($item) => [
            'label' => (string) ($item['label'] ?? ''),
            'value' => in_array($item['value'] ?? null, ['si', 'no'], true) ? $item['value'] : null,
        ])->all();

        $view = $kind === 'milk' ? 'movimientos.ficha_control_leche' : 'movimientos.ficha_control_hojuela';

        return $this->pdfService->stream($view, [
            'productName' => self::PRODUCTS[$kind]['name'],
            'data' => $data,
            'items' => $normalize($data['items'] ?? []),
            'extras' => $normalize($data['extras'] ?? []),
            'weighing' => in_array($data['weighing'] ?? null, ['si', 'no'], true) ? $data['weighing'] : null,
        ], "ficha-control-{$kind}.pdf", 'a4', 'portrait');
    }

    /** PDF de la tarjeta de control interno con las filas confirmadas en el modal. */
    public function tarjetaPdf(Request $request)
    {
        $kind = $this->kindFrom($request);
        $data = $this->payload($request);

        $rows = collect($data['rows'] ?? [])->map(fn ($row) => [
            'date' => $this->displayDate($row['date'] ?? null),
            'time' => (string) ($row['time'] ?? ''),
            'in' => $this->numberOrNull($row['in'] ?? null),
            'out' => $this->numberOrNull($row['out'] ?? null),
            'by' => (string) ($row['by'] ?? ''),
        ])->all();

        return $this->pdfService->stream('movimientos.tarjeta_control', [
            'productName' => self::PRODUCTS[$kind]['card_name'],
            'folio' => (string) ($data['folio'] ?? ''),
            'month' => (string) ($data['month'] ?? ''),
            'rows' => $rows,
            'minRows' => 18,
        ], "tarjeta-control-{$kind}.pdf", 'a4', 'portrait');
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
        return Responsible::query()
            ->with('person')
            ->get()
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

    private function detailProductIds(string $kind): array
    {
        return DetailProduct::query()
            ->whereHas('product', function ($q) use ($kind) {
                $q->where(function ($inner) use ($kind) {
                    foreach (self::PRODUCTS[$kind]['keywords'] as $keyword) {
                        $inner->orWhereRaw('UPPER(title) LIKE ?', ["%{$keyword}%"]);
                    }
                });
            })
            ->pluck('id')
            ->all();
    }

    private function activeStorekeeperName(): string
    {
        $person = Responsible::query()
            ->where('type', 'storekeeper')
            ->where('active', true)
            ->with('person')
            ->latest('start_date')
            ->first()?->person;

        return $person ? trim(collect([$person->names, $person->father_lastname])->filter()->implode(' ')) : '';
    }

    private function kindFrom(Request $request): string
    {
        return $request->validate(['product' => 'required|in:milk,oat'])['product'];
    }

    private function payload(Request $request): array
    {
        $request->validate(['payload' => 'required|string|max:200000']);
        $data = json_decode($request->input('payload'), true);
        abort_unless(is_array($data), 422, 'Los datos del documento no son válidos.');

        return $data;
    }

    private function numberOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $number = (float) str_replace(',', '', (string) $value);

        return is_finite($number) ? $number : null;
    }

    private function displayDate($value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
