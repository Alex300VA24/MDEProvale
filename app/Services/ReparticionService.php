<?php

namespace App\Services;

use App\Models\Association;
use App\Models\DistributionPeriod;
use App\Models\Racion;
use Illuminate\Support\Collection;

class ReparticionService
{
    public function getActiveRacion(int $year, int $month): ?Racion
    {
        return Racion::forMonth($year, $month)->first();
    }

    public function getConfiguration(Racion $racion, int $year, int $month): array
    {
        $period = DistributionPeriod::with('assignments')
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $days = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        return [
            'id' => $period?->id,
            'year' => $year,
            'month' => $month,
            'service_days' => $period?->service_days ?? $days,
            'milk_grams_per_beneficiary' => (float) ($period?->milk_grams_per_beneficiary ?? $racion->racion_leche_militros ?? config('distribution.defaults.milk_grams_per_beneficiary')),
            'oat_grams_per_beneficiary' => (float) ($period?->oat_grams_per_beneficiary ?? $racion->racion_hojuelas_gramos ?? config('distribution.defaults.oat_grams_per_beneficiary')),
            'milk_can_grams' => (float) ($period?->milk_can_grams ?? config('distribution.defaults.milk_can_grams')),
            'oat_bag_grams' => (float) ($period?->oat_bag_grams ?? config('distribution.defaults.oat_bag_grams')),
            'milk_cans_per_box' => (int) ($period?->milk_cans_per_box ?? config('distribution.defaults.milk_cans_per_box')),
            'oat_kg_per_sack' => (int) ($period?->oat_kg_per_sack ?? config('distribution.defaults.oat_kg_per_sack')),
            'assignments' => $period ? $period->assignments->keyBy('association_id') : collect(),
        ];
    }

    public function calculateQuantities(int $beneficiaries, array $configuration): array
    {
        $days = (int) $configuration['service_days'];
        $milkTotal = (int) round(
            ($beneficiaries * (float) $configuration['milk_grams_per_beneficiary'] * $days)
            / (float) $configuration['milk_can_grams'],
            0,
            PHP_ROUND_HALF_UP
        );
        $oatTotal = (int) round(
            ($beneficiaries * (float) $configuration['oat_grams_per_beneficiary'] * $days)
            / (float) $configuration['oat_bag_grams'],
            0,
            PHP_ROUND_HALF_UP
        );

        return [
            'milk_total' => $milkTotal,
            'milk_boxes' => intdiv($milkTotal, (int) $configuration['milk_cans_per_box']),
            'milk_loose_cans' => $milkTotal % (int) $configuration['milk_cans_per_box'],
            'oat_total' => $oatTotal,
            'oat_sacks' => intdiv($oatTotal, (int) $configuration['oat_kg_per_sack']),
            'oat_loose_kg' => $oatTotal % (int) $configuration['oat_kg_per_sack'],
            'daily_milk' => round($milkTotal / $days, 6),
            'daily_oat' => round($oatTotal / $days, 6),
        ];
    }

    public function buildReport(Racion $racion, int $year, int $month): array
    {
        $configuration = $this->getConfiguration($racion, $year, $month);
        $days = (int) $configuration['service_days'];
        $calendarDays = (int) date('t', strtotime("$year-$month-01"));
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, min($days, $calendarDays));
        /** @var Collection $assignments */
        $assignments = $configuration['assignments'];

        $associations = Association::with([
            'placeSector.sector',
            'partners' => function ($query) use ($endDate) {
                $query->select(['id', 'association_id', 'date_begin', 'date_end'])
                    ->where(fn ($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate))
                    ->where(fn ($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate));
            },
            'partners.beneficiaries' => function ($query) use ($endDate) {
                $query->select(['id', 'partner_id'])
                    ->whereHas('histories', function ($history) use ($endDate) {
                        $history->where(fn ($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate))
                            ->where(fn ($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate));
                    });
            },
        ])->orderBy('code')->get()->map(function ($association) use ($configuration, $assignments, $endDate) {
            $baseBeneficiaries = $association->partners->sum(fn ($partner) => $partner->beneficiaries->count());
            $assignment = $assignments->get($association->id);
            $adjustment = (int) ($assignment?->beneficiary_adjustment ?? 0);
            $beneficiaries = max(0, $baseBeneficiaries + $adjustment);
            $quantities = $this->calculateQuantities($beneficiaries, $configuration);

            return [
                'id' => $association->id,
                'codigo' => $association->code ?? $association->id,
                'nombre' => $association->name,
                'presidenta' => $association->getPresidentNameAt($endDate) ?? '',
                'direccion' => $association->address ?? '',
                'sector' => optional(optional($association->placeSector)->sector)->title ?? '',
                'beneficiarios_base' => $baseBeneficiaries,
                'ajuste_beneficiarios' => $adjustment,
                'beneficiarios' => $beneficiaries,
                'vuelta' => max(1, (int) ($assignment?->route_number ?? 1)),
                'observacion' => $assignment?->observation ?? '',
                'dias' => (int) $configuration['service_days'],
                'leche_gramos' => (float) $configuration['milk_grams_per_beneficiary'],
                'hojuelas_gramos' => (float) $configuration['oat_grams_per_beneficiary'],
                'leche_litros' => $quantities['milk_total'],
                'leche_total' => $quantities['milk_total'],
                'leche_cajas' => $quantities['milk_boxes'],
                'leche_tarros' => $quantities['milk_loose_cans'],
                'hojuelas_kg' => $quantities['oat_total'],
                'hojuelas_sacos' => $quantities['oat_sacks'],
                'hojuelas_kilos' => $quantities['oat_loose_kg'],
                'racion_diaria_leche' => $quantities['daily_milk'],
                'racion_diaria_hojuelas' => $quantities['daily_oat'],
            ];
        })->filter(fn ($club) => $club['beneficiarios_base'] > 0 || $club['ajuste_beneficiarios'] !== 0)
            ->sortBy([['vuelta', 'asc'], ['codigo', 'asc']])
            ->values();

        $routes = $associations->groupBy('vuelta')->map(function (Collection $clubs, $route) use ($configuration) {
            $milk = (int) $clubs->sum('leche_total');
            $oat = (int) $clubs->sum('hojuelas_kg');

            return [
                'vuelta' => (int) $route,
                'clubs' => $clubs->values(),
                'total_beneficiarios' => (int) $clubs->sum('beneficiarios'),
                'total_leche' => $milk,
                'leche_cajas' => intdiv($milk, (int) $configuration['milk_cans_per_box']),
                'leche_tarros' => $milk % (int) $configuration['milk_cans_per_box'],
                'total_hojuelas' => $oat,
                'hojuelas_sacos' => intdiv($oat, (int) $configuration['oat_kg_per_sack']),
                'hojuelas_kilos' => $oat % (int) $configuration['oat_kg_per_sack'],
            ];
        })->sortKeys()->values();

        $totalMilk = (int) $associations->sum('leche_total');
        $totalOat = (int) $associations->sum('hojuelas_kg');
        unset($configuration['assignments']);

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $days,
            'start_date' => sprintf('%04d-%02d-01', $year, $month),
            'end_date' => $endDate,
            'configuration' => $configuration,
            'racion_leche_ml' => $configuration['milk_grams_per_beneficiary'],
            'racion_hojuelas_gr' => $configuration['oat_grams_per_beneficiary'],
            'associations' => $associations,
            'vueltas' => $routes,
            'total_beneficiarios' => (int) $associations->sum('beneficiarios'),
            'total_leche_litros' => $totalMilk,
            'total_leche_tarros' => $totalMilk,
            'total_leche_cajas' => intdiv($totalMilk, (int) $configuration['milk_cans_per_box']),
            'total_leche_sueltos' => $totalMilk % (int) $configuration['milk_cans_per_box'],
            'total_hojuelas_kg' => $totalOat,
            'total_hojuelas_sacos' => intdiv($totalOat, (int) $configuration['oat_kg_per_sack']),
            'total_hojuelas_sueltos' => $totalOat % (int) $configuration['oat_kg_per_sack'],
        ];
    }
}
