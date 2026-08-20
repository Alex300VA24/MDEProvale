<?php

namespace App\Http\Controllers;

use App\Services\ReparticionService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class ReparticionController extends Controller
{
    private ReparticionService $reparticionService;

    public function __construct(ReparticionService $reparticionService)
    {
        $this->reparticionService = $reparticionService;
    }

    public function index(Request $request)
    {
        $currentYear = (int) $request->get('year', date('Y'));
        $currentMonth = (int) $request->get('month', date('n'));

        $racion = $this->reparticionService->getActiveRacion($currentYear);
        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Responsables y Raciones.');
        }

        $report = $this->reparticionService->buildReport($racion, $currentYear, $currentMonth);

        return view('movimientos.reparticion_tabla', [
            'associations' => $report['associations'],
            'currentYear' => $report['year'],
            'currentMonth' => $report['month'],
            'daysInMonth' => $report['days_in_month'],
            'racionLecheMl' => $report['racion_leche_ml'],
            'racionHojuelasGr' => $report['racion_hojuelas_gr'],
            'totalBeneficiarios' => $report['total_beneficiarios'],
            'totalLecheLitros' => $report['total_leche_litros'],
            'totalHojuelasKg' => $report['total_hojuelas_kg'],
        ]);
    }

    public function pdf(Request $request)
    {
        $currentYear = (int) $request->get('year', date('Y'));
        $currentMonth = (int) $request->get('month', date('n'));

        $racion = $this->reparticionService->getActiveRacion($currentYear);
        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Responsables y Raciones.');
        }

        $report = $this->reparticionService->buildReport($racion, $currentYear, $currentMonth);

        $monthName = date('F', strtotime($report['end_date']));

        $pdf = PDF::loadView('movimientos.reparticion', [
            'clubs' => $report['associations'],
            'currentYear' => $report['year'],
            'currentMonth' => $report['month'],
            'monthName' => $monthName,
            'daysInMonth' => $report['days_in_month'],
            'racionLecheMl' => $report['racion_leche_ml'],
            'racionHojuelasGr' => $report['racion_hojuelas_gr'],
            'totalBeneficiarios' => $report['total_beneficiarios'],
            'totalLecheLitros' => $report['total_leche_litros'],
            'totalHojuelasKg' => $report['total_hojuelas_kg'],
        ]);

        return $pdf->setPaper('landscape')->stream('reparticion-' . $report['year'] . '-' . $report['month'] . '.pdf');
    }
}
