<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Racion;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class ReparticionController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = $request->get('year', date('Y'));
        $currentMonth = $request->get('month', date('n'));
        $daysInMonth = date('t', strtotime("$currentYear-$currentMonth-01"));

        $racion = Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $endDate = "$currentYear-$currentMonth-" . $daysInMonth;

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
                'leche_litros' => $lecheTarros,
                'leche_cajas' => $lecheCajas,
                'leche_tarros' => $lecheTarrosSueltos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_sacos' => $hojuelasSacos,
                'hojuelas_kilos' => $hojuelasKilosSueltos,
            ];
        })->filter(function ($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        $totalBeneficiarios = $associations->sum('beneficiarios');
        $totalLecheLitros = $associations->sum('leche_litros');
        $totalHojuelasKg = $associations->sum('hojuelas_kg');

        return view('movimientos.reparticion_tabla', compact(
            'associations',
            'currentYear',
            'currentMonth',
            'daysInMonth',
            'racionLecheMl',
            'racionHojuelasGr',
            'totalBeneficiarios',
            'totalLecheLitros',
            'totalHojuelasKg'
        ));
    }

    public function pdf(Request $request)
    {
        $currentYear = $request->get('year', date('Y'));
        $currentMonth = $request->get('month', date('n'));
        $daysInMonth = date('t', strtotime("$currentYear-$currentMonth-01"));

        $racion = Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $endDate = "$currentYear-$currentMonth-" . $daysInMonth;

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
                'leche_litros' => $lecheTarros,
                'leche_cajas' => $lecheCajas,
                'leche_tarros' => $lecheTarrosSueltos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_sacos' => $hojuelasSacos,
                'hojuelas_kilos' => $hojuelasKilosSueltos,
            ];
        })->filter(function ($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        $monthName = date('F', strtotime($endDate));

        $pdf = PDF::loadView('movimientos.reparticion', [
            'clubs' => $associations,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'monthName' => $monthName,
            'daysInMonth' => $daysInMonth,
            'racionLecheMl' => $racionLecheMl,
            'racionHojuelasGr' => $racionHojuelasGr,
            'totalBeneficiarios' => $associations->sum('beneficiarios'),
            'totalLecheLitros' => $associations->sum('leche_litros'),
            'totalHojuelasKg' => $associations->sum('hojuelas_kg'),
        ]);

        return $pdf->setPaper('landscape')->stream('reparticion-' . $currentYear . '-' . date('m') . '.pdf');
    }
}
