<?php

namespace App\Http\Controllers;

use App\Models\PvlReportRun;
use App\Services\ReportePvlPdfService;
use App\Services\Pvl\PvlSupportingReportService;
use Illuminate\Http\Response;

class PvlReportPdfController extends Controller
{
    public function preview(PvlReportRun $pvlReportRun, string $type, ReportePvlPdfService $pdfService, PvlSupportingReportService $supportingReport)
    {
        [$pdf, $filename] = $this->makePdf($pvlReportRun, $type, $pdfService, $supportingReport);

        return $pdf->stream($filename, ['Attachment' => false]);
    }

    public function download(PvlReportRun $pvlReportRun, string $type, ReportePvlPdfService $pdfService, PvlSupportingReportService $supportingReport)
    {
        [$pdf, $filename] = $this->makePdf($pvlReportRun, $type, $pdfService, $supportingReport);

        return $pdf->download($filename);
    }

    private function makePdf(PvlReportRun $run, string $type, ReportePvlPdfService $service, PvlSupportingReportService $supportingReport): array
    {
        abort_unless($run->canGenerate(), Response::HTTP_UNPROCESSABLE_ENTITY, 'El reporte requiere revisión antes de generar el PDF.');
        abort_unless(in_array($type, ['pvl', 'racion-a', 'informe'], true), 404);
        abort_if($type === 'informe' && $run->report_type !== 'AMBOS', 404, 'El informe sustentatorio requiere ambos anexos.');

        $key = $type === 'pvl' ? 'pvl' : 'racion_a';
        $data = $type === 'informe'
            ? $supportingReport->build($run)
            : data_get($run->validated_data_json, $key);
        abort_unless(is_array($data), 404, 'Este análisis no contiene el tipo de reporte solicitado.');

        $period = sprintf('%04d-%02d', $run->year, $run->month);
        $filename = match ($type) {
            'pvl' => 'FORMATO-PVL-'.$period.'.pdf',
            'racion-a' => 'RACION-A-'.$period.'.pdf',
            default => 'INFORME-SUSTENTATORIO-PVL-'.$period.'.pdf',
        };

        return [$service->forType($type, $data), $filename];
    }
}
