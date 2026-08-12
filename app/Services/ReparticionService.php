<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Racion;

class ReparticionService
{
    public function getActiveRacion(int $year): ?Racion
    {
        return Racion::where('year', $year)->where('active', true)->first();
    }

    /**
     * Calcula el reporte de repartición mensual (solo lectura).
     * Devuelve los comités con al menos un beneficiario activo en el mes,
     * sus raciones calculadas y los totales del período.
     */
    public function buildReport(Racion $racion, int $year, int $month): array
    {
        $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
        $endDate = "$year-$month-" . $daysInMonth;

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $associations = Association::with([
            'placeSector.sector',
            'partners' => function ($query) use ($endDate) {
                $query->select(['id', 'association_id', 'date_begin', 'date_end'])
                    ->where(function ($q) use ($endDate) {
                        $q->whereNull('date_begin')
                            ->orWhere('date_begin', '<=', $endDate);
                    })
                    ->where(function ($q) use ($endDate) {
                        $q->whereNull('date_end')
                            ->orWhere('date_end', '>=', $endDate);
                    });
            },
            'partners.beneficiaries' => function ($q) use ($endDate) {
                $q->select(['id', 'partner_id'])
                    ->whereHas('histories', function ($hq) use ($endDate) {
                        $hq->where(function ($q) use ($endDate) {
                            $q->whereNull('date_begin')
                                ->orWhere('date_begin', '<=', $endDate);
                        })
                        ->where(function ($q) use ($endDate) {
                            $q->whereNull('date_end')
                                ->orWhere('date_end', '>=', $endDate);
                        });
                    });
            }
        ])->get()->map(function ($association) use ($racionLecheMl, $racionHojuelasGr, $daysInMonth) {
            $totalBeneficiaries = 0;

            foreach ($association->partners as $partner) {
                $totalBeneficiaries += $partner->beneficiaries->count();
            }

            $presidenta = $association->getPresidentName() ?? '';

            $lecheTarros = round(($totalBeneficiaries * $daysInMonth * $racionLecheMl) / 410);
            $lecheCajas = intdiv((int) $lecheTarros, 48);
            $lecheTarrosSueltos = (int) $lecheTarros % 48;
            $hojuelasKg = round(($totalBeneficiaries * $daysInMonth * $racionHojuelasGr) / 1000);
            $hojuelasSacos = intdiv((int) $hojuelasKg, 30);
            $hojuelasKilosSueltos = (int) $hojuelasKg % 30;

            return [
                'id' => $association->id,
                'codigo' => $association->code ?? $association->id,
                'nombre' => $association->name,
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'sector' => optional(optional($association->placeSector)->sector)->title ?? '',
                'beneficiarios' => $totalBeneficiaries,
                'dias' => $daysInMonth,
                'leche_ml' => $racionLecheMl,
                'hojuelas_gramos' => $racionHojuelasGr,
                'leche_litros' => (int) $lecheTarros,
                'leche_cajas' => $lecheCajas,
                'leche_tarros' => $lecheTarrosSueltos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_sacos' => $hojuelasSacos,
                'hojuelas_kilos' => $hojuelasKilosSueltos,
            ];
        })->filter(function ($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'end_date' => $endDate,
            'racion_leche_ml' => $racionLecheMl,
            'racion_hojuelas_gr' => $racionHojuelasGr,
            'associations' => $associations,
            'total_beneficiarios' => $associations->sum('beneficiarios'),
            'total_leche_litros' => $associations->sum('leche_litros'),
            'total_hojuelas_kg' => $associations->sum('hojuelas_kg'),
        ];
    }
}
