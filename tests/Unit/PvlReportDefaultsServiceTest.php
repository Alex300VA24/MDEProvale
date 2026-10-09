<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\Pvl\PvlReportDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PvlReportDefaultsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_defaults_before_ai_without_overwriting_existing_database_values(): void
    {
        Setting::put(PvlReportDefaultsService::SETTING_KEY, json_encode([
            'municipality_name' => 'MUNICIPALIDAD CONFIGURADA',
            'administration_director' => 'DIRECTOR PREDETERMINADO',
            'supporting_sender_name' => 'REMITENTE PREDETERMINADO',
        ]));
        $context = [
            'pvl' => [
                'municipalidad' => 'MUNICIPALIDAD DESDE BD',
                'director_administracion' => null,
            ],
            'racion_a' => null,
            'trazabilidad' => [],
            'meta' => [],
        ];

        $result = (new PvlReportDefaultsService())->apply($context, ['sender_name' => 'REMITENTE DEL PERIODO']);

        $this->assertSame('MUNICIPALIDAD DESDE BD', data_get($result, 'pvl.municipalidad'));
        $this->assertSame('DIRECTOR PREDETERMINADO', data_get($result, 'pvl.director_administracion'));
        $this->assertSame('REMITENTE DEL PERIODO', data_get($result, 'report_metadata.sender_name'));
        $this->assertContains('pvl.director_administracion', data_get($result, 'meta.campos_predeterminados'));
        $this->assertSame('PREDETERMINADO', data_get($result, 'trazabilidad.0.origen'));
    }

    public function test_it_applies_row_templates_to_every_missing_row_and_can_seed_an_empty_collection(): void
    {
        Setting::put(PvlReportDefaultsService::SETTING_KEY, json_encode([
            'pvl_food_supplier' => 'PROVEEDOR PREDETERMINADO',
            'distribution_product' => 'LECHE EVAPORADA',
            'distribution_date' => '2026-09-15',
        ]));
        $context = [
            'pvl' => [
                'total_compras_alimentos' => 0,
                'compras_alimentos' => [
                ['producto' => 'LECHE', 'proveedor' => null],
                ['producto' => 'HOJUELAS', 'proveedor' => 'PROVEEDOR REAL'],
                ],
            ],
            'racion_a' => ['distribuciones' => []],
            'trazabilidad' => [],
            'meta' => [],
        ];

        $result = (new PvlReportDefaultsService())->apply($context);

        $this->assertSame('PROVEEDOR PREDETERMINADO', data_get($result, 'pvl.compras_alimentos.0.proveedor'));
        $this->assertSame('PROVEEDOR REAL', data_get($result, 'pvl.compras_alimentos.1.proveedor'));
        $this->assertSame(0, data_get($result, 'pvl.total_compras_alimentos'));
        $this->assertSame('LECHE EVAPORADA', data_get($result, 'racion_a.distribuciones.0.producto'));
        $this->assertSame('2026-09-15', data_get($result, 'racion_a.distribuciones.0.fecha_distribucion'));
        $this->assertContains('racion_a.distribuciones.0.producto', data_get($result, 'meta.campos_predeterminados'));
    }

    public function test_it_invalidates_a_purchase_total_when_a_default_amount_changes_its_lines(): void
    {
        Setting::put(PvlReportDefaultsService::SETTING_KEY, json_encode(['pvl_food_amount' => '1250.50']));
        $context = [
            'pvl' => ['compras_alimentos' => [], 'total_compras_alimentos' => 0],
            'racion_a' => null,
            'trazabilidad' => [],
            'meta' => [],
        ];

        $result = (new PvlReportDefaultsService())->apply($context);

        $this->assertNull(data_get($result, 'pvl.total_compras_alimentos'));
        $this->assertSame('1250.50', data_get($result, 'pvl.compras_alimentos.0.importe'));
    }
}
