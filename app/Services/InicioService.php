<?php

namespace App\Services;

use App\Models\Pecosa;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Datos del panel de inicio: tarjetas KPI y gráficas.
 */
class InicioService
{
    /** Año mínimo seleccionable en las gráficas del panel de inicio. */
    private const MIN_YEAR = 2019;

    public function __construct(
        private DashboardRepositoryInterface $dashboard,
        private MonthClosureService $closures
    ) {
    }

    /**
     * Cada gráfica se filtra de forma independiente: cambiar el año de una no
     * afecta a las demás.
     *
     * @param  array{anio_pecosas?:mixed, anio_productos?:mixed, periodo_anio?:mixed, periodo_mes?:mixed}  $filters
     */
    public function panel(array $filters): array
    {
        $currentYear = now()->year;

        $yearPecosas = $this->clampYear((int) ($filters['anio_pecosas'] ?? $currentYear), $currentYear);
        $yearProductos = $this->clampYear((int) ($filters['anio_productos'] ?? $currentYear), $currentYear);

        [$periodYear, $periodMonth] = $this->dashboardPeriod($filters);
        [$cardStart, $cardEnd] = $this->periodRange($periodYear, $periodMonth);

        $hasRosterSnapshot = $this->dashboard->hasPartnerRosterSnapshot($cardStart);
        $partners = $this->dashboard->partnerCounts($cardStart, $cardEnd, $hasRosterSnapshot);
        $beneficiaries = $this->dashboard->beneficiaryCounts($cardStart, $cardEnd);
        $totalComites = $this->dashboard->committeeCount($cardStart);

        $dualRole = $this->dualRoleBreakdown($cardStart, $cardEnd, $hasRosterSnapshot);
        $rosterAudit = $this->rosterAudit($periodYear, $periodMonth, [
            'socios' => ['relations' => $partners['relations'], 'corrected' => $partners['people']],
            'beneficiarios' => ['relations' => $beneficiaries['relations'], 'corrected' => $beneficiaries['people']],
            'clubes' => ['relations' => $totalComites, 'corrected' => $totalComites],
        ], $dualRole);

        [$pecosaStart, $pecosaEnd] = Pecosa::deliveryPeriodRange($periodYear, $periodMonth);
        $pecosaData = $this->pecosasByMonth($yearPecosas);
        [$lecheData, $hojuelasData] = $this->productsByMonth($yearProductos);

        return [
            'stats' => [
                // Los cards muestran el padrón tal como fue registrado. Los
                // totales depurados permanecen en el detalle de auditoría.
                'total_socios' => $partners['relations'],
                'total_beneficiarios' => $beneficiaries['relations'],
                'total_comites' => $totalComites,
                'total_pecosas' => $this->dashboard->pecosaCountBetween($pecosaStart->toDateString(), $pecosaEnd->toDateString()),
                'period' => ['year' => $periodYear, 'month' => $periodMonth],
                'roster_audit' => $rosterAudit,
                'dual_role' => $dualRole,
                'stock_total' => $this->dashboard->stockTotal(),
                'stock_productos' => $this->stockProductosForPeriod($periodYear, $periodMonth),
            ],
            'pecosas_por_mes' => [
                'data' => $pecosaData,
                'total_anio' => array_sum($pecosaData),
                'anio' => $yearPecosas,
            ],
            'productos_distribuidos' => [
                'leche' => $lecheData,
                'hojuelas' => $hojuelasData,
                'anio' => $yearProductos,
            ],
            // Socios y beneficiarios vigentes en el mismo periodo del resumen,
            // por solapamiento de fechas de alta/baja con el rango.
            'socios_vs_beneficiarios' => [
                'socios' => $partners['people'],
                'beneficiarios' => $this->dashboard->beneficiaryPeopleInPeriod($cardStart, $cardEnd),
                'anio' => $periodYear,
                'mes' => $periodMonth,
            ],
            'anio_min' => self::MIN_YEAR,
            'anio_actual' => $currentYear,
            'top_comites' => $this->dashboard->topCommittees($cardStart, $cardEnd)
                ->map(fn ($item) => [
                    'codigo' => $item->code,
                    'nombre' => $item->club,
                    'direccion' => $item->address,
                    'total' => (int) $item->total,
                ])
                ->values(),
        ];
    }

    /**
     * PECOSAs por mes del año. Se amplía el rango a dic. del año anterior porque
     * una entrega en la última semana de un mes se contabiliza en el mes
     * siguiente, pudiendo desplazar diciembre hacia enero.
     *
     * @return int[]
     */
    private function pecosasByMonth(int $year): array
    {
        $data = array_fill(0, 12, 0);

        foreach ($this->dashboard->pecosaDeliveryDates(($year - 1) . '-12-01', $year . '-12-31') as $date) {
            $effective = Pecosa::effectiveDeliveryDate($date);

            if ((int) $effective->year === $year) {
                $data[$effective->month - 1]++;
            }
        }

        return $data;
    }

    /**
     * Salidas reales de Leche y Hojuelas, ubicadas en el mes del ingreso del
     * lote (no en la fecha de la PECOSA: la base de origen a veces parte una
     * ración en dos PECOSAs a ambos lados del corte de fin de mes).
     *
     * @return array{0:int[], 1:int[]} [leche, hojuelas]
     */
    private function productsByMonth(int $year): array
    {
        $leche = array_fill(0, 12, 0);
        $hojuelas = array_fill(0, 12, 0);

        foreach ($this->dashboard->distributedByMonth($year) as $item) {
            $month = (int) $item->mes - 1;
            if ($month < 0 || $month > 11) {
                continue;
            }
            if (stripos($item->product, 'leche') !== false) {
                $leche[$month] += (int) $item->total;
            } elseif (stripos($item->product, 'hojuela') !== false) {
                $hojuelas[$month] += (int) $item->total;
            }
        }

        return [$leche, $hojuelas];
    }

    /**
     * Stock mensual de Hojuelas y Leche para el período seleccionado.
     *
     * Devuelve para cada alimento lo ingresado durante el mes, lo utilizado
     * (salidas sobre el lote del mes) y el restante (ingresado - utilizado;
     * negativo implica faltante). El flag `cerrado` indica si el período ya fue
     * cerrado por el administrador, momento recién en el que tienen sentido definitivo.
     *
     * @return array<int, array{key:string, name:string, unit:string, ingresado:int, utilizado:int, restante:int, stock:int, cerrado:bool}>
     */
    private function stockProductosForPeriod(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $rows = $this->dashboard->oatAndMilkLots($start, $end);
        $cerrado = $this->closures->isPeriodClosed($year, $month);

        return collect([
            ['key' => 'hojuelas', 'name' => 'Hojuelas', 'needle' => 'hojuela'],
            ['key' => 'leche', 'name' => 'Leche', 'needle' => 'leche'],
        ])->map(function ($definition) use ($rows, $cerrado) {
            $matching = $rows->filter(
                fn ($item) => stripos((string) $item->product, $definition['needle']) !== false
            );

            $ingresado = (int) $matching->sum('quantity');
            $utilizado = (int) $matching->sum('total_used');
            $restante = $ingresado - $utilizado;

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'unit' => $matching->first() ? (string) $matching->first()->unit : '',
                'ingresado' => $ingresado,
                'utilizado' => $utilizado,
                'restante' => $restante,
                'stock' => $restante,
                'cerrado' => $cerrado,
            ];
        })->values()->all();
    }

    /** Acota un año al rango [MIN_YEAR, año actual]. */
    private function clampYear(int $year, int $currentYear): int
    {
        return ($year < self::MIN_YEAR || $year > $currentYear) ? $currentYear : $year;
    }

    /**
     * Rango de fechas [inicio, fin] del periodo. mes = 0 abarca el año completo.
     *
     * @return array{0: string, 1: string}
     */
    private function periodRange(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            return ["$year-01-01", "$year-12-31"];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    /** Periodo pedido o, por defecto, el mes con la actividad más reciente. */
    private function dashboardPeriod(array $filters): array
    {
        if (($filters['periodo_anio'] ?? '') !== '' && ($filters['periodo_mes'] ?? '') !== '') {
            return [
                $this->clampYear((int) $filters['periodo_anio'], now()->year),
                max(1, min(12, (int) $filters['periodo_mes'])),
            ];
        }

        $latestHistory = $this->dashboard->latestBeneficiaryHistoryDate();
        $latestPecosa = $this->dashboard->latestPecosaDeliveryDate();
        $dates = collect([
            $latestHistory ? Carbon::parse($latestHistory) : null,
            $latestPecosa ? Pecosa::effectiveDeliveryDate($latestPecosa) : null,
        ])->filter();
        $latest = $dates->sortByDesc(fn ($date) => $date->format('Y-m'))->first() ?? now();

        return [$latest->year, $latest->month];
    }

    /**
     * Personas que, en el mismo período, figuran como socias y como
     * beneficiarias LAC, GES o DIS. Se cuentan en ambos totales porque son dos
     * roles distintos, pero una sola vez dentro de cada total corregido.
     *
     * @return array{LAC: int, GES: int, DIS: int, total: int}
     */
    private function dualRoleBreakdown(string $start, string $end, bool $useRosterSnapshot): array
    {
        $dual = $this->dashboard->dualRole($start, $end, $useRosterSnapshot);

        return [
            'LAC' => $dual['byType']['LAC'] ?? 0,
            'GES' => $dual['byType']['GES'] ?? 0,
            'DIS' => $dual['byType']['DIS'] ?? 0,
            'total' => $dual['total'],
        ];
    }

    /**
     * Adjunta la fuente y las observaciones inmutables de los padrones
     * históricos. Los valores actuales se calculan desde la BD y se comparan
     * con la auditoría para no ocultar futuras diferencias.
     */
    private function rosterAudit(int $year, int $month, array $current, array $dualRole): ?array
    {
        // Los avisos de un período solo se exponen cuando ese mes ya fue
        // cerrado por el administrador.
        if (! $this->closures->isPeriodClosed($year, $month)) {
            return null;
        }

        $period = sprintf('%04d-%02d', $year, $month);
        $audit = config("roster_audits.{$period}");

        if (! $audit) {
            return null;
        }

        foreach ($audit['metrics'] as $key => &$metric) {
            $expectedCorrected = $metric['corrected'];
            $metric['corrected_expected'] = $expectedCorrected;
            $metric['database'] = (int) $current[$key]['relations'];
            $metric['corrected'] = (int) $current[$key]['corrected'];
            $metric['verified'] = $metric['database'] === (int) $metric['migrated']
                && $metric['corrected'] === (int) $expectedCorrected;
        }
        unset($metric);

        $expectedDualRole = $audit['dual_role'];
        $audit['dual_role'] = $dualRole + [
            'expected' => $expectedDualRole,
            'verified' => $dualRole === $expectedDualRole,
        ];
        $audit['period'] = $period;

        return $audit;
    }
}
