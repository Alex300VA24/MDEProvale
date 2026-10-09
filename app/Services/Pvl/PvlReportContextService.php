<?php

namespace App\Services\Pvl;

use App\Models\BeneficiaryHistory;
use App\Models\DetailPecosa;
use App\Models\DistributionPeriod;
use App\Models\Pecosa;
use App\Models\Product;
use App\Models\PvlDocument;
use App\Models\Racion;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PvlReportContextService
{
    public function build(string $reportType, int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $trace = [];

        $pvl = null;
        if (in_array($reportType, ['PVL', 'AMBOS'], true)) {
            $pvl = $this->pvlData($startDate, $endDate, $trace);
        }

        $ration = null;
        $meta = ['beneficiarios_sin_zona' => 0];
        if (in_array($reportType, ['RACION_A', 'AMBOS'], true)) {
            [$ration, $meta] = $this->rationData($startDate, $endDate, $trace);
        }

        return [
            'report_type' => $reportType,
            'period' => $period,
            'pvl' => $pvl,
            'racion_a' => $ration,
            'trazabilidad' => $trace,
            'meta' => $meta,
            'documentos_respaldo' => PvlDocument::query()
                ->where('period', $period)
                ->orderBy('id')
                ->get(['id', 'document_type', 'file_name', 'index_status'])
                ->map(fn (PvlDocument $document) => [
                    'id' => $document->id,
                    'tipo_documento' => $document->document_type,
                    'archivo' => $document->file_name,
                    'estado_indexacion' => $document->index_status,
                ])
                ->values()
                ->all(),
        ];
    }

    private function pvlData(Carbon $start, Carbon $end, array &$trace): array
    {
        $transactions = Transaction::query()
            ->with(['detailProduct.product.uom', 'typeTransaction'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('typeTransaction', fn ($query) => $query->whereRaw('LOWER(TRIM(title)) = ?', ['ingreso']))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $lines = $transactions->map(fn (Transaction $transaction) => $this->purchaseLine($transaction))->values();
        [$complementary, $food] = $lines->partition(fn (array $line) => $this->isComplementary($line['producto']));

        $trace[] = [
            'campo' => 'compras_alimentos',
            'valor' => $food->count(),
            'origen' => 'BD',
            'entidad' => 'transactions',
            'referencia' => 'Ingresos del periodo '.$start->format('Y-m'),
        ];
        $trace[] = [
            'campo' => 'compras_insumos',
            'valor' => $complementary->count(),
            'origen' => 'BD',
            'entidad' => 'transactions',
            'referencia' => 'Ingresos complementarios del periodo '.$start->format('Y-m'),
        ];

        return array_merge($this->baseIdentity($start), [
            'fecha_hora_impresion' => now()->format('d/m/Y h:i A'),
            'numero_expediente' => null,
            'codigo_envio' => null,
            'compras_alimentos' => $food->values()->all(),
            'total_compras_alimentos' => round((float) $food->sum('importe'), 2),
            'compras_insumos' => $complementary->values()->all(),
            'total_compras_insumos' => round((float) $complementary->sum('importe'), 2),
            'donaciones' => [],
            'total_donaciones' => null,
            'total_gastos_operativos' => null,
            'total_gastos' => null,
            'financiamiento' => [
                'saldo_inicial_tesoro' => null,
                'transferencia_tesoro' => null,
                'recursos_directamente_recaudados' => null,
                'foncomun' => null,
                'donaciones' => null,
                'intereses' => null,
                'total_recursos' => null,
                'saldo_final' => null,
            ],
            'presidente_comite_administracion' => null,
            'director_administracion' => null,
        ]);
    }

    /** @return array{0:array,1:array} */
    private function rationData(Carbon $start, Carbon $end, array &$trace): array
    {
        $racion = Racion::forMonth($start->year, $start->month)->first();
        $distributionPeriod = DistributionPeriod::query()
            ->where('year', $start->year)
            ->where('month', $start->month)
            ->first();
        $serviceDays = $distributionPeriod?->service_days;

        [$pecosaStart, $pecosaEnd] = Pecosa::deliveryPeriodRange($start->year, $start->month);
        $details = DetailPecosa::query()
            ->with(['pecosa', 'detailProduct.product.uom'])
            ->whereHas('pecosa', fn ($query) => $query->whereBetween('delivery_date', [
                $pecosaStart->toDateString(),
                $pecosaEnd->toDateString(),
            ]))
            ->get();

        $distributions = $details
            ->groupBy(fn (DetailPecosa $detail) => ($detail->pecosa?->delivery_date?->format('Y-m-d') ?? '').'|'.$this->productName($detail))
            ->map(function (Collection $items) use ($start, $serviceDays, $distributionPeriod) {
                $first = $items->first();
                $quantity = (float) $items->sum(fn (DetailPecosa $detail) => $detail->delivered_quantity > 0
                    ? $detail->delivered_quantity
                    : $detail->quantity);
                $kg = $this->quantityInKg($first, $quantity, $distributionPeriod);
                $attentionEnd = $serviceDays
                    ? $start->copy()->addDays(max(0, $serviceDays - 1))->min($start->copy()->endOfMonth())
                    : null;

                return [
                    'producto' => $this->productName($first),
                    'cantidad_kg' => $kg,
                    'cantidad_litros' => null,
                    'fecha_distribucion' => $first->pecosa?->delivery_date?->format('d/m/Y'),
                    'fecha_inicio_atencion' => $serviceDays ? $start->format('d/m/Y') : null,
                    'fecha_fin_atencion' => $attentionEnd?->format('d/m/Y'),
                ];
            })
            ->values()
            ->all();

        [$beneficiaries, $unclassified] = $this->beneficiaryCounts($start, $end);
        $committeeCount = $details->pluck('pecosa.association_id')->filter()->unique()->count();

        $rations = [];
        if ($racion) {
            $oat = Product::where('title', 'like', '%HOJUEL%')->value('title');
            $milk = Product::where('title', 'like', '%LECHE%')->value('title');
            $rations[] = [
                'alimento1' => $oat,
                'alimento1_gramos' => (float) $racion->racion_hojuelas_gramos,
                'alimento1_cc' => null,
                'alimento2' => $milk,
                'alimento2_gramos' => null,
                'alimento2_cc' => (float) $racion->racion_leche_militros,
                'alimento3' => null,
                'alimento3_gramos' => null,
                'alimento3_cc' => null,
                'dias_prioridad_1' => $serviceDays,
                'dias_prioridad_2' => $serviceDays,
                'tipo_racion' => 'PREPARADA',
            ];
        }

        $trace[] = [
            'campo' => 'raciones_compuestas',
            'valor' => count($rations),
            'origen' => 'BD',
            'entidad' => 'raciones',
            'referencia' => 'Ración vigente del periodo '.$start->format('Y-m'),
        ];
        $trace[] = [
            'campo' => 'distribuciones',
            'valor' => count($distributions),
            'origen' => 'BD',
            'entidad' => 'pecosas y detail_pecosas',
            'referencia' => 'Periodo efectivo de entrega '.$start->format('Y-m'),
        ];
        $trace[] = [
            'campo' => 'beneficiarios',
            'valor' => ($beneficiaries['rural']['total'] ?? 0) + ($beneficiaries['urbana']['total'] ?? 0),
            'origen' => 'BD',
            'entidad' => 'beneficiary_histories',
            'referencia' => 'Beneficiarios activos del periodo '.$start->format('Y-m'),
        ];

        return [array_merge($this->baseIdentity($start), [
            'numero_expediente' => null,
            'codigo_envio' => null,
            'raciones_un_alimento' => [],
            'raciones_compuestas' => $rations,
            'distribuciones' => $distributions,
            'certificados' => [],
            'composicion' => [],
            'beneficiarios' => $beneficiaries,
            'cantidad_comites_atendidos' => $committeeCount,
            'presidente_comite_administracion' => null,
            'representante_ministerio_salud' => null,
            'profesion_representante_salud' => null,
        ]), ['beneficiarios_sin_zona' => $unclassified]];
    }

    private function baseIdentity(Carbon $period): array
    {
        $months = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        return [
            // Se aplican después como predeterminados auditables, antes de la IA.
            'municipalidad' => null,
            'tipo_municipalidad' => null,
            'departamento' => null,
            'provincia' => null,
            'mes_reportado' => $months[$period->month],
            'anio_reportado' => $period->year,
            'fecha_reporte' => now()->format('d/m/Y'),
        ];
    }

    private function purchaseLine(Transaction $transaction): array
    {
        $product = $transaction->product_name ?: $transaction->detailProduct?->product?->title;
        $uom = $transaction->uom_title ?: $transaction->detailProduct?->product?->uom?->title;
        [$series, $number] = $this->splitDocumentNumber($transaction->document_number);
        $quantity = (float) $transaction->quantity;
        $kg = null;
        $liters = null;

        if ($this->isKilogramUnit($uom)) {
            $kg = $quantity;
        } elseif ($this->isLiterUnit($uom)) {
            $liters = $quantity;
        }

        return [
            'clasificacion' => null,
            'producto' => $product,
            'marca' => null,
            'origen' => null,
            'proveedor' => null,
            'ruc' => null,
            'tipo_comprobante' => null,
            'serie' => $series,
            'numero_comprobante' => $number,
            'fecha_emision' => $transaction->transaction_date?->format('d/m/Y'),
            'cantidad_kg' => $kg,
            'cantidad_litros' => $liters,
            'importe' => (float) $transaction->total_price,
        ];
    }

    /** @return array{0:?string,1:?string} */
    private function splitDocumentNumber(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [null, null];
        }

        if (preg_match('/^([A-Za-z0-9]+)[-\s]+([A-Za-z0-9]+)$/', $value, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, $value];
    }

    private function isComplementary(?string $product): bool
    {
        $name = mb_strtolower((string) $product);

        return collect(['azúcar', 'azucar', 'cocoa', 'canela', 'pan', 'clavo'])
            ->contains(fn (string $term) => str_contains($name, $term));
    }

    /** @return array{0:array,1:int} */
    private function beneficiaryCounts(Carbon $start, Carbon $end): array
    {
        $empty = [
            'menores_1_anio' => 0,
            'ninos_1_a_6' => 0,
            'madres_gestantes' => 0,
            'madres_lactantes' => 0,
            'personas_7_a_13' => 0,
            'personas_tbc' => 0,
            'ancianos' => 0,
            'discapacitados' => 0,
            'total' => 0,
        ];
        $counts = ['rural' => $empty, 'urbana' => $empty];
        $unclassified = 0;

        $histories = BeneficiaryHistory::query()
            ->with([
                'typeBenefit',
                'beneficiary.person.placeSector.place',
                'beneficiary.partner.association.placeSector.place',
            ])
            ->whereDate('date_begin', '<=', $end->toDateString())
            ->where(fn ($dates) => $dates->whereNull('date_end')->orWhereDate('date_end', '>=', $start->toDateString()))
            ->orderByDesc('date_begin')
            ->get()
            ->unique('beneficiary_id');

        foreach ($histories as $history) {
            $beneficiary = $history->beneficiary;
            $person = $beneficiary?->person;
            if (! $person) {
                continue;
            }

            $place = $person->placeSector?->place?->title
                ?: $beneficiary->partner?->association?->placeSector?->place?->title;
            $zone = $this->zoneBucket($place);
            if ($zone === null) {
                $unclassified++;
                continue;
            }

            $category = $this->beneficiaryCategory($history, $person->birthdate, $end);
            if ($category === null) {
                continue;
            }

            $counts[$zone][$category]++;
        }

        foreach (['rural', 'urbana'] as $zone) {
            $counts[$zone]['total'] = array_sum(array_diff_key($counts[$zone], ['total' => true]));
        }

        return [$counts, $unclassified];
    }

    private function zoneBucket(?string $place): ?string
    {
        $place = mb_strtolower(trim((string) $place));
        if (str_contains($place, 'rural')) {
            return 'rural';
        }
        if (str_contains($place, 'urb') || str_contains($place, 'zona')) {
            return 'urbana';
        }

        return null;
    }

    private function beneficiaryCategory(BeneficiaryHistory $history, $birthdate, Carbon $end): ?string
    {
        $benefit = mb_strtolower(trim((string) ($history->typeBenefit?->title ?? '')));
        $abbreviation = mb_strtoupper(trim((string) ($history->typeBenefit?->abbreviation ?? '')));

        if ($history->is_disabled || $abbreviation === 'DIS' || str_contains($benefit, 'discap')) {
            return 'discapacitados';
        }
        if ($abbreviation === 'TBC' || str_contains($benefit, 'tbc')) {
            return 'personas_tbc';
        }
        if ($abbreviation === 'GES' || str_contains($benefit, 'gestante')) {
            return 'madres_gestantes';
        }
        if ($abbreviation === 'LAC' || str_contains($benefit, 'lactante')) {
            return 'madres_lactantes';
        }
        if ($abbreviation === 'ADU' || str_contains($benefit, 'adulto mayor')) {
            return 'ancianos';
        }
        if (! $birthdate) {
            return null;
        }

        $birth = Carbon::parse($birthdate);
        if ($birth->greaterThan($end)) {
            return null;
        }
        if ($birth->diffInMonths($end) < 12) {
            return 'menores_1_anio';
        }

        $age = $birth->diffInYears($end);
        if ($age <= 6) {
            return 'ninos_1_a_6';
        }
        if ($age <= 13) {
            return 'personas_7_a_13';
        }
        if ($age >= 65) {
            return 'ancianos';
        }

        return null;
    }

    private function productName(DetailPecosa $detail): ?string
    {
        return $detail->product_name ?: $detail->detailProduct?->product?->title;
    }

    private function quantityInKg(DetailPecosa $detail, float $quantity, ?DistributionPeriod $period): ?float
    {
        $uom = $detail->uom_title ?: $detail->detailProduct?->product?->uom?->title;
        if ($this->isKilogramUnit($uom)) {
            return round($quantity, 2);
        }

        $abbreviation = mb_strtoupper((string) ($detail->product_abbreviation ?: $detail->detailProduct?->product?->abbreviation));
        if ($abbreviation === 'LEC' && $period) {
            return round($quantity * (float) $period->milk_can_grams / 1000, 2);
        }
        if ($abbreviation === 'HOJ' && $period) {
            return round($quantity * (float) $period->oat_bag_grams / 1000, 2);
        }

        return null;
    }

    private function isKilogramUnit(?string $uom): bool
    {
        $value = mb_strtolower((string) $uom);

        return str_contains($value, 'kilogram') || preg_match('/\bkg\b/', $value) === 1;
    }

    private function isLiterUnit(?string $uom): bool
    {
        $value = mb_strtolower((string) $uom);

        return str_contains($value, 'litro') || preg_match('/\bl\b/', $value) === 1;
    }
}
