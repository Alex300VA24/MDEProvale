<?php

namespace Tests\Unit;

use App\Services\Pvl\PvlReportDataMapper;
use PHPUnit\Framework\TestCase;

class PvlReportDataMapperTest extends TestCase
{
    public function test_it_maps_exact_pvl_and_ration_shapes_without_inventing_missing_values(): void
    {
        $mapper = new PvlReportDataMapper();
        $context = [
            'pvl' => ['municipalidad' => 'MDE', 'anio_reportado' => 2026],
            'racion_a' => ['municipalidad' => 'MDE', 'anio_reportado' => 2026],
        ];
        $result = $mapper->map(['data' => ['pvl' => [], 'racion_a' => []]], $context, 'AMBOS');

        $this->assertSame(array_keys($mapper->pvlShape()), array_keys($result['pvl']));
        $this->assertSame(array_keys($mapper->rationShape()), array_keys($result['racion_a']));
        $this->assertNull($result['pvl']['numero_expediente']);
        $this->assertNull($result['racion_a']['codigo_envio']);
    }

    public function test_it_rejects_invalid_dates_and_non_integer_counts_instead_of_coercing_them(): void
    {
        $mapper = new PvlReportDataMapper();
        $ration = $mapper->rationShape();
        $ration['fecha_reporte'] = '31/02/2026';
        $ration['cantidad_comites_atendidos'] = 'tres';
        $ration['beneficiarios']['rural']['menores_1_anio'] = 'desconocido';
        $ration['raciones_compuestas'][0]['dias_prioridad_1'] = 2.5;

        $result = $mapper->map(['data' => ['racion_a' => $ration]], [], 'RACION_A')['racion_a'];

        $this->assertNull($result['fecha_reporte']);
        $this->assertNull($result['cantidad_comites_atendidos']);
        $this->assertNull($result['beneficiarios']['rural']['menores_1_anio']);
        $this->assertNull($result['raciones_compuestas'][0]['dias_prioridad_1']);
    }

    public function test_real_ai_evidence_replaces_defaults_but_not_database_values(): void
    {
        $mapper = new PvlReportDataMapper();
        $context = [
            'pvl' => [
                'municipalidad' => 'MUNICIPALIDAD DESDE BD',
                'director_administracion' => 'DIRECTOR PREDETERMINADO',
            ],
            'meta' => ['campos_predeterminados' => ['pvl.director_administracion']],
        ];
        $aiOutput = ['data' => ['pvl' => [
            'municipalidad' => 'MUNICIPALIDAD DOCUMENTAL',
            'director_administracion' => 'DIRECTOR REAL DEL DOCUMENTO',
        ]]];

        $result = $mapper->map($aiOutput, $context, 'PVL')['pvl'];

        $this->assertSame('MUNICIPALIDAD DESDE BD', $result['municipalidad']);
        $this->assertSame('DIRECTOR REAL DEL DOCUMENTO', $result['director_administracion']);
    }
}
