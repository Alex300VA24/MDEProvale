<?php

namespace App\Http\Controllers;

use App\Services\ReparticionService;
use App\Models\VerifiedDocument;
use App\Services\PDFService;
use App\Services\SchedulingService;
use App\Services\VerifiedDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReparticionController extends Controller
{
    private ReparticionService $reparticionService;

    public function __construct(
        ReparticionService $reparticionService,
        private VerifiedDocumentService $verifiedDocumentService,
        private SchedulingService $schedulingService,
        private PDFService $pdfService
    ) {
        $this->reparticionService = $reparticionService;
    }

    public function index(Request $request)
    {
        $currentYear = (int) $request->get('year', date('Y'));
        $currentMonth = (int) $request->get('month', date('n'));

        $racion = $this->reparticionService->getActiveRacion($currentYear, $currentMonth);
        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el período ' . $currentMonth . '/' . $currentYear . '. Configure las raciones en Responsables y Raciones.');
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

        $racion = $this->reparticionService->getActiveRacion($currentYear, $currentMonth);
        if (!$racion) {
            return redirect()->route('movimientos.index')
                ->with('error', 'No hay ración configurada para el período ' . $currentMonth . '/' . $currentYear . '. Configure las raciones en Responsables y Raciones.');
        }

        $report = $this->reparticionService->buildReport($racion, $currentYear, $currentMonth);

        $monthName = date('F', strtotime($report['end_date']));

        $viewData = [
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
        ];

        $identifier = sprintf(
            'REP-%04d-%02d-%s',
            $report['year'],
            $report['month'],
            strtoupper(Str::random(8))
        );
        $filename = 'reparticion-' . $report['year'] . '-' . sprintf('%02d', $report['month']) . '.pdf';

        [, $contents, $safeFilename] = $this->verifiedDocumentService->issue(
            VerifiedDocument::TYPE_DISTRIBUTION_REGISTER,
            $identifier,
            [
                'periodo' => sprintf('%04d-%02d', $report['year'], $report['month']),
                'comites' => $report['associations']->count(),
                'beneficiarios' => $report['total_beneficiarios'],
            ],
            'movimientos.reparticion',
            $viewData,
            $filename,
            $request->user()?->id,
            'a4',
            'landscape'
        );

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
        ]);
    }

    public function pdfConFirma(Request $request)
    {
        $currentYear = (int) $request->get('year', date('Y'));
        $currentMonth = (int) $request->get('month', date('n'));
        $sector = $request->filled('sector') ? (string) $request->get('sector') : null;

        abort_unless($currentMonth >= 1 && $currentMonth <= 12, 422, 'El mes seleccionado no es válido.');

        $clubs = $this->schedulingService->generateProgramacionEntrega(
            $currentMonth,
            $currentYear,
            $sector
        );

        $filename = 'reparticion-con-firma-' . $currentYear . '-' . sprintf('%02d', $currentMonth) . '.pdf';

        return $this->pdfService->stream('movimientos.reparticion_con_firma', [
            'clubs' => $clubs,
            'sector' => $sector,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ], $filename, 'a4', 'landscape');
    }
}
