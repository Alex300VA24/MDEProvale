<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Pecosa;
use App\Models\Partner;
use App\Models\Product;
use App\Services\SchedulingService;
use App\Services\ReparticionService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private SchedulingService $schedulingService;

    public function __construct(
        SchedulingService $schedulingService,
        private ReparticionService $reparticionService
    )
    {
        $this->schedulingService = $schedulingService;
    }

    public function programacionEntrega(Request $request)
    {
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));
        $sector = $request->get('sector', '');

        abort_if(
            $this->reparticionService->isBeforeFirstRecordedPeriod((int) $anio, (int) $mes),
            404,
            $this->reparticionService->periodRecordNotFoundMessage((int) $anio, (int) $mes)
        );
        abort_unless(
            $this->reparticionService->hasIngresoForPeriod((int) $anio, (int) $mes),
            422,
            $this->reparticionService->ingresoRequiredMessage((int) $anio, (int) $mes)
        );

        $clubes = $this->schedulingService->generateProgramacionEntrega((int)$mes, (int)$anio, $sector ?: null);

        $data = [
            'clubes' => $clubes,
            'sector' => $sector,
            'mes' => $mes,
            'anio' => $anio,
        ];

        $pdf = PDF::loadView('programacion_entrega', $data);
        return $pdf->setPaper('A4', 'landscape')->stream('programacion-entrega-' . $anio . '-' . sprintf('%02d', $mes) . '.pdf');
    }
}
