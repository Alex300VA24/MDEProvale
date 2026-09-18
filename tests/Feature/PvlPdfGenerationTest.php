<?php

namespace Tests\Feature;

use App\Models\PvlReportRun;
use App\Services\Pvl\PvlSupportingReportService;
use App\Services\ReportePvlPdfService;
use Tests\TestCase;

class PvlPdfGenerationTest extends TestCase
{
    public function test_official_templates_and_supporting_report_generate_pdf_without_exception(): void
    {
        $service = $this->app->make(ReportePvlPdfService::class);
        $pvl = json_decode(file_get_contents(base_path('plv_blade_templates/examples/ejemplo_pvl_junio_2026.json')), true);
        $ration = json_decode(file_get_contents(base_path('plv_blade_templates/examples/ejemplo_racion_junio_2026.json')), true);

        $this->assertStringStartsWith('%PDF', $service->formatoPvl($pvl)->output());
        $this->assertStringStartsWith('%PDF', $service->formatoRacionA($ration)->output());

        $run = new PvlReportRun([
            'report_type' => 'AMBOS',
            'month' => 6,
            'year' => 2026,
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'input_snapshot_json' => [
                'report_metadata' => [
                    'report_number' => '123-2026-MDE/GDS/SGPS',
                    'recipient_name' => 'Jefatura de Programas Sociales',
                    'sender_name' => 'Responsable PVL',
                ],
                'documentos_respaldo' => [],
            ],
            'validated_data_json' => ['pvl' => $pvl, 'racion_a' => $ration],
            'warnings_json' => [],
            'sources_json' => [[
                'campo' => 'beneficiarios',
                'origen' => 'BD',
                'referencia' => 'Beneficiarios activos del periodo 2026-06',
            ]],
        ]);
        $supportingData = $this->app->make(PvlSupportingReportService::class)->build($run);

        $this->assertStringStartsWith('%PDF', $service->informeSustentatorio($supportingData)->output());
    }
}
