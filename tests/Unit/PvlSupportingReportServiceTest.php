<?php

namespace Tests\Unit;

use App\Models\PvlReportRun;
use App\Services\Pvl\PvlSupportingReportService;
use Tests\TestCase;

class PvlSupportingReportServiceTest extends TestCase
{
    public function test_it_only_accredits_submission_with_code_and_indexed_receipt(): void
    {
        $run = new PvlReportRun([
            'report_type' => 'AMBOS',
            'month' => 6,
            'year' => 2026,
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'input_snapshot_json' => [
                'report_metadata' => ['report_number' => '123-2026-MDE/GDS/SGPS'],
                'documentos_respaldo' => [[
                    'id' => 9,
                    'tipo_documento' => 'constancia_envio',
                    'archivo' => 'cargo-contraloria.pdf',
                    'estado_indexacion' => 'INDEXADO',
                ]],
            ],
            'validated_data_json' => $this->validatedData('ENV-2026-06'),
            'warnings_json' => [],
            'sources_json' => [[
                'campo' => 'beneficiarios',
                'origen' => 'BD',
                'entidad' => 'beneficiary_histories',
                'referencia' => 'Beneficiarios activos del periodo 2026-06',
            ]],
        ]);

        $report = (new PvlSupportingReportService())->build($run);

        $this->assertTrue($report['envio_acreditado']);
        $this->assertSame('CUMPLIMIENTO Y ENVÍO ACREDITADOS', $report['estado_acreditacion']);
        $this->assertSame(15, $report['resumen']['beneficiarios']);
        $this->assertStringContainsString('constancia documental', $report['conclusiones'][2]);
    }

    public function test_it_does_not_claim_submission_without_receipt(): void
    {
        $run = new PvlReportRun([
            'report_type' => 'AMBOS',
            'month' => 6,
            'year' => 2026,
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'input_snapshot_json' => ['documentos_respaldo' => []],
            'validated_data_json' => $this->validatedData('ENV-2026-06'),
            'warnings_json' => [],
            'sources_json' => [],
        ]);

        $report = (new PvlSupportingReportService())->build($run);

        $this->assertFalse($report['envio_acreditado']);
        $this->assertStringContainsString('pendiente de acreditar', mb_strtolower($report['conclusiones'][2]));
    }

    private function validatedData(?string $shippingCode): array
    {
        return [
            'pvl' => [
                'municipalidad' => 'MUNICIPALIDAD DISTRITAL DE LA ESPERANZA',
                'fecha_reporte' => '21/07/2026',
                'codigo_envio' => $shippingCode,
                'compras_alimentos' => [['importe' => 120.50]],
                'compras_insumos' => [],
                'total_gastos' => 120.50,
                'financiamiento' => ['total_recursos' => 200, 'saldo_final' => 79.50],
            ],
            'racion_a' => [
                'codigo_envio' => $shippingCode,
                'raciones_compuestas' => [['alimento1' => 'LECHE']],
                'raciones_un_alimento' => [],
                'distribuciones' => [['producto' => 'LECHE']],
                'certificados' => [['numero_certificado' => 'CERT-1']],
                'composicion' => [['producto' => 'LECHE', 'porcentaje' => 100]],
                'beneficiarios' => ['rural' => ['total' => 5], 'urbana' => ['total' => 10]],
                'cantidad_comites_atendidos' => 2,
            ],
        ];
    }
}
