<?php

namespace App\Services\Pvl;

use App\Models\DetailPecosa;
use App\Models\Pecosa;
use App\Repositories\Contracts\PvlReportRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Informe trimestral para Contraloría construido solo con lo que el sistema
 * registra (ingresos, distribución, beneficiarios, comités). No usa IA ni
 * valores predeterminados: lo que el sistema no registra se declara aparte
 * como "no disponible" en lugar de inventarse.
 */
class PvlQuarterlyReportService
{
    private const MONTHS = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];

    private const CATEGORIES = [
        'menores_1_anio' => 'Niños menores de 1 año',
        'ninos_1_a_6' => 'Niños de 1 a 6 años',
        'madres_gestantes' => 'Madres gestantes',
        'madres_lactantes' => 'Madres lactantes',
        'personas_7_a_13' => 'Personas de 7 a 13 años',
        'personas_tbc' => 'Personas con TBC',
        'ancianos' => 'Adultos mayores',
        'discapacitados' => 'Personas con discapacidad',
    ];

    public function __construct(private PvlReportContextService $context, private PvlReportRepositoryInterface $reports)
    {
    }

    public function build(int $year, int $quarter): array
    {
        $firstMonth = ($quarter - 1) * 3 + 1;
        $months = [];
        $purchases = collect();
        $distributions = collect();
        $monthlyByProduct = [];
        $latestBeneficiaries = null;
        $latestRation = null;
        $committees = 0;

        foreach (range($firstMonth, $firstMonth + 2) as $month) {
            $data = $this->context->build('AMBOS', $year, $month);
            $pvl = $data['pvl'] ?? [];
            $ration = $data['racion_a'] ?? [];

            $monthPurchases = collect($pvl['compras_alimentos'] ?? [])->merge($pvl['compras_insumos'] ?? []);
            [$pecosaStart, $pecosaEnd] = Pecosa::deliveryPeriodRange($year, $month);
            $monthDistributions = $this->reports->pecosaDetailsBetween($pecosaStart, $pecosaEnd)->map(fn (DetailPecosa $detail) => [
                'producto' => $detail->product_name ?: $detail->detailProduct?->product?->title ?: 'Sin producto',
                'unidad' => $detail->uom_title ?: $detail->detailProduct?->product?->uom?->title ?: 'unidad',
                'cantidad' => (float) ($detail->delivered_quantity > 0 ? $detail->delivered_quantity : $detail->quantity),
            ])->values();
            foreach ($monthDistributions->groupBy('producto') as $product => $items) {
                $monthlyByProduct[$product][$month] = round((float) $items->sum('cantidad'), 2);
            }
            $beneficiaries = $ration['beneficiarios'] ?? null;
            $beneficiaryTotal = (int) data_get($beneficiaries, 'rural.total', 0) + (int) data_get($beneficiaries, 'urbana.total', 0);

            $purchases = $purchases->merge($monthPurchases);
            $distributions = $distributions->merge($monthDistributions);
            $committees = max($committees, (int) ($ration['cantidad_comites_atendidos'] ?? 0));

            if ($beneficiaryTotal > 0) {
                $latestBeneficiaries = $beneficiaries;
                $latestRation = ($ration['raciones_compuestas'][0] ?? null) ?: $latestRation;
            }

            $months[] = [
                'month' => $month,
                'name' => self::MONTHS[$month],
                'ingresos_registros' => $monthPurchases->count(),
                'ingresos_importe' => round((float) $monthPurchases->sum('importe'), 2),
                'distribuciones' => $monthDistributions->count(),
                'unidades_distribuidas' => round((float) $monthDistributions->sum('cantidad'), 2),
                'comites_atendidos' => (int) ($ration['cantidad_comites_atendidos'] ?? 0),
                'beneficiarios' => $beneficiaryTotal,
            ];
        }

        $byProduct = $this->purchasesByProduct($purchases);
        $distByProduct = $this->distributionByProduct($distributions);
        $categories = $this->categories($latestBeneficiaries);
        $zones = [
            'rural' => (int) data_get($latestBeneficiaries, 'rural.total', 0),
            'urbana' => (int) data_get($latestBeneficiaries, 'urbana.total', 0),
        ];

        $totals = [
            'ingresos_registros' => $purchases->count(),
            'ingresos_importe' => round((float) $purchases->sum('importe'), 2),
            'distribuciones' => $distributions->count(),
            'unidades_distribuidas' => round((float) $distributions->sum('cantidad'), 2),
            'comites_atendidos' => $committees,
            'beneficiarios' => array_sum($zones),
        ];

        $quarterText = ['', 'I', 'II', 'III', 'IV'][$quarter].' TRIMESTRE';
        $hasData = $totals['ingresos_registros'] > 0 || $totals['distribuciones'] > 0 || $totals['beneficiarios'] > 0;

        return [
            'year' => $year,
            'quarter' => $quarter,
            'quarter_text' => $quarterText,
            'period_text' => self::MONTHS[$firstMonth].' – '.self::MONTHS[$firstMonth + 2].' '.$year,
            'has_data' => $hasData,
            'months' => $months,
            'totals' => $totals,
            'ingresos_por_producto' => $byProduct,
            'distribucion_por_producto' => $distByProduct,
            'distribucion_mensual' => collect($monthlyByProduct)->map(fn (array $byMonth, string $product) => [
                'producto' => $product,
                'data' => array_map(fn (array $m) => $byMonth[$m['month']] ?? 0, $months),
            ])->values()->all(),
            'beneficiarios_categorias' => $categories,
            'beneficiarios_zonas' => $zones,
            'racion' => $latestRation ? [
                'hojuelas_gramos' => $latestRation['alimento1_gramos'] ?? null,
                'leche_cc' => $latestRation['alimento2_cc'] ?? null,
            ] : null,
            'considerandos' => $this->considerandos($totals, $byProduct, $distByProduct, $quarterText, $year),
            'no_disponible' => [
                'Saldo inicial, transferencias del Tesoro, FONCOMÚN, recursos directamente recaudados e intereses.',
                'Gastos operativos y donaciones valorizadas.',
                'Certificados de calidad, composición nutricional y lotes registrados en formato oficial.',
                'Número de expediente y código de envío del portal de Contraloría.',
            ],
        ];
    }

    private function purchasesByProduct(Collection $purchases): array
    {
        return $purchases
            ->groupBy(fn (array $line) => $line['producto'] ?: 'Sin producto')
            ->map(fn (Collection $items, string $product) => [
                'producto' => $product,
                'registros' => $items->count(),
                'cantidad_kg' => round((float) $items->sum('cantidad_kg'), 2),
                'cantidad_litros' => round((float) $items->sum('cantidad_litros'), 2),
                'importe' => round((float) $items->sum('importe'), 2),
            ])
            ->sortByDesc('importe')
            ->values()
            ->all();
    }

    private function distributionByProduct(Collection $distributions): array
    {
        return $distributions
            ->groupBy(fn (array $line) => $line['producto'] ?: 'Sin producto')
            ->map(fn (Collection $items, string $product) => [
                'producto' => $product,
                'unidad' => $items->first()['unidad'],
                'entregas' => $items->count(),
                'cantidad' => round((float) $items->sum('cantidad'), 2),
            ])
            ->sortByDesc('cantidad')
            ->values()
            ->all();
    }

    private function categories(?array $beneficiaries): array
    {
        $result = [];
        foreach (self::CATEGORIES as $key => $label) {
            $result[] = [
                'key' => $key,
                'label' => $label,
                'rural' => (int) data_get($beneficiaries, "rural.$key", 0),
                'urbana' => (int) data_get($beneficiaries, "urbana.$key", 0),
            ];
        }

        return $result;
    }

    private function considerandos(array $totals, array $byProduct, array $distByProduct, string $quarterText, int $year): array
    {
        $lines = [];
        $lines[] = sprintf(
            'En el %s del año %d se registraron %d ingreso(s) de productos por un valor de S/ %s.',
            $quarterText,
            $year,
            $totals['ingresos_registros'],
            number_format($totals['ingresos_importe'], 2, '.', ',')
        );
        if ($byProduct) {
            $lines[] = sprintf('El producto de mayor valor ingresado fue %s, con S/ %s.', $byProduct[0]['producto'], number_format($byProduct[0]['importe'], 2, '.', ','));
        }
        $lines[] = sprintf(
            'Se registraron %d línea(s) de entrega mediante PECOSA, que atendieron a %d comité(s) de base.',
            $totals['distribuciones'],
            $totals['comites_atendidos']
        );
        if ($distByProduct) {
            $lines[] = sprintf('El producto más distribuido fue %s, con %s %s(s).', $distByProduct[0]['producto'], number_format($distByProduct[0]['cantidad'], 0, '.', ','), mb_strtolower($distByProduct[0]['unidad']));
        }
        $lines[] = sprintf('La población beneficiaria activa al cierre del periodo asciende a %s persona(s).', number_format($totals['beneficiarios']));

        return $lines;
    }
}
