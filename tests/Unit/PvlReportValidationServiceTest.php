<?php

namespace Tests\Unit;

use App\Models\PvlReportRun;
use App\Services\Pvl\PvlReportValidationService;
use Tests\TestCase;

class PvlReportValidationServiceTest extends TestCase
{
    private PvlReportValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PvlReportValidationService();
    }

    public function test_it_recalculates_purchase_resources_and_final_balance(): void
    {
        $data = ['pvl' => $this->validPvl()];
        $result = $this->service->validate($data, $this->aiOutput(), $this->context());

        $this->assertSame(150.0, $result['data']['pvl']['total_compras_alimentos']);
        $this->assertSame(25.0, $result['data']['pvl']['total_compras_insumos']);
        $this->assertSame(185.0, $result['data']['pvl']['total_gastos']);
        $this->assertSame(260.0, $result['data']['pvl']['financiamiento']['total_recursos']);
        $this->assertSame(75.0, $result['data']['pvl']['financiamiento']['saldo_final']);
    }

    public function test_it_detects_duplicate_invoices(): void
    {
        $pvl = $this->validPvl();
        $pvl['compras_alimentos'][] = $pvl['compras_alimentos'][0];

        $result = $this->service->validate(['pvl' => $pvl], $this->aiOutput(), $this->context());

        $this->assertContains('COMPROBANTE_DUPLICADO', array_column($result['findings'], 'code'));
        $this->assertSame(PvlReportRun::REQUIERE_REVISION, $result['status']);
    }

    public function test_it_detects_source_conflicts(): void
    {
        $ai = $this->aiOutput();
        $ai['conflictos'][] = [
            'campo' => 'certificados.0.numero_lote',
            'tipo' => 'CONFLICTO_FUENTES',
            'opciones' => [['valor' => '002'], ['valor' => '003']],
        ];

        $result = $this->service->validate(['pvl' => $this->validPvl()], $ai, $this->context());

        $this->assertContains('CONFLICTO_FUENTES', array_column($result['findings'], 'code'));
        $this->assertSame(PvlReportRun::REQUIERE_REVISION, $result['status']);
    }

    public function test_it_ignores_stale_ai_missing_entry_when_backend_has_confirmed_value(): void
    {
        $ai = $this->aiOutput();
        $ai['datos_faltantes'][] = [
            'campo' => 'pvl.municipalidad',
            'obligatorio' => true,
            'motivo' => 'No encontrada por el modelo.',
        ];

        $result = $this->service->validate(['pvl' => $this->validPvl()], $ai, $this->context());

        $matching = collect($result['findings'])->where('field', 'pvl.municipalidad');
        $this->assertTrue($matching->isEmpty());
    }

    public function test_it_keeps_missing_values_null_and_blocks_required_financial_data(): void
    {
        $pvl = $this->validPvl();
        $pvl['financiamiento']['foncomun'] = null;

        $result = $this->service->validate(['pvl' => $pvl], $this->aiOutput(), $this->context());

        $this->assertNull($result['data']['pvl']['financiamiento']['foncomun']);
        $this->assertNull($result['data']['pvl']['financiamiento']['total_recursos']);
        $this->assertContains('DATO_FALTANTE', array_column($result['findings'], 'code'));
    }

    public function test_it_recalculates_beneficiaries_and_accepts_composition_near_one_hundred(): void
    {
        $ration = $this->validRation();
        $ration['beneficiarios']['urbana']['total'] = 999;

        $result = $this->service->validate(['racion_a' => $ration], $this->aiOutput(), $this->context());

        $this->assertSame(36, $result['data']['racion_a']['beneficiarios']['urbana']['total']);
        $this->assertNotContains('COMPOSICION_INVALIDA', array_column($result['findings'], 'code'));
    }

    public function test_it_warns_when_monthly_balance_has_no_continuity(): void
    {
        $previous = new PvlReportRun();
        $previous->validated_data_json = ['pvl' => ['financiamiento' => ['saldo_final' => 99.0]]];

        $result = $this->service->validate(['pvl' => $this->validPvl()], $this->aiOutput(), $this->context(), $previous);

        $this->assertContains('CONTINUIDAD_SALDO', array_column($result['findings'], 'code'));
    }

    public function test_purchase_and_later_distribution_are_not_automatically_an_error(): void
    {
        $result = $this->service->validate(
            ['pvl' => $this->validPvl(), 'racion_a' => $this->validRation()],
            $this->aiOutput(),
            $this->context()
        );

        $this->assertNotContains('COMPRA_DISTRIBUCION_MES', array_column($result['findings'], 'code'));
    }

    public function test_incomplete_ration_evidence_blocks_pdf_generation(): void
    {
        $ration = $this->validRation();
        $ration['certificados'][0]['numero_certificado'] = null;

        $result = $this->service->validate(['racion_a' => $ration], $this->aiOutput(), $this->context());

        $this->assertSame(PvlReportRun::REQUIERE_REVISION, $result['status']);
        $this->assertContains('racion_a.certificados.0.numero_certificado', array_column($result['findings'], 'field'));
    }

    private function validPvl(): array
    {
        $purchase = fn (float $amount, string $number) => [
            'producto' => 'LECHE', 'proveedor' => 'PROVEEDOR', 'ruc' => '00123456789',
            'tipo_comprobante' => 'FACTURA', 'serie' => 'F001', 'numero_comprobante' => $number,
            'fecha_emision' => '10/06/2026', 'importe' => $amount,
        ];

        return [
            'municipalidad' => 'MUNICIPALIDAD DISTRITAL DE LA ESPERANZA',
            'tipo_municipalidad' => 'MUNICIPALIDAD DISTRITAL', 'departamento' => 'LA LIBERTAD',
            'provincia' => 'TRUJILLO', 'mes_reportado' => 'JUNIO', 'anio_reportado' => 2026,
            'fecha_reporte' => '30/06/2026', 'numero_expediente' => 'EXP-1', 'codigo_envio' => 'COD-1',
            'compras_alimentos' => [$purchase(150, '0001')], 'total_compras_alimentos' => null,
            'compras_insumos' => [$purchase(25, '0002')], 'total_compras_insumos' => null,
            'donaciones' => [], 'total_donaciones' => null, 'total_gastos_operativos' => 10,
            'total_gastos' => null,
            'financiamiento' => [
                'saldo_inicial_tesoro' => 20, 'transferencia_tesoro' => 200,
                'recursos_directamente_recaudados' => 10, 'foncomun' => 15,
                'donaciones' => 5, 'intereses' => 10, 'total_recursos' => null, 'saldo_final' => null,
            ],
            'presidente_comite_administracion' => 'PRESIDENTE', 'director_administracion' => 'DIRECTOR',
        ];
    }

    private function validRation(): array
    {
        $beneficiaries = [
            'menores_1_anio' => 1, 'ninos_1_a_6' => 2, 'madres_gestantes' => 3,
            'madres_lactantes' => 4, 'personas_7_a_13' => 5, 'personas_tbc' => 6,
            'ancianos' => 7, 'discapacitados' => 8, 'total' => null,
        ];

        return [
            'municipalidad' => 'MUNICIPALIDAD DISTRITAL DE LA ESPERANZA',
            'tipo_municipalidad' => 'MUNICIPALIDAD DISTRITAL', 'departamento' => 'LA LIBERTAD',
            'provincia' => 'TRUJILLO', 'mes_reportado' => 'JUNIO', 'anio_reportado' => 2026,
            'fecha_reporte' => '30/06/2026', 'numero_expediente' => 'EXP-1', 'codigo_envio' => 'COD-1',
            'raciones_un_alimento' => [],
            'raciones_compuestas' => [[
                'alimento1' => 'HOJUELA', 'alimento1_gramos' => 51.5, 'alimento1_cc' => null,
                'alimento2' => 'LECHE', 'alimento2_gramos' => null, 'alimento2_cc' => 44,
                'dias_prioridad_1' => 20, 'dias_prioridad_2' => 20, 'tipo_racion' => 'PREPARADA',
            ]],
            'distribuciones' => [[
                'producto' => 'LECHE', 'cantidad_kg' => null, 'cantidad_litros' => 100,
                'fecha_distribucion' => '05/06/2026', 'fecha_inicio_atencion' => '01/06/2026',
                'fecha_fin_atencion' => '20/06/2026',
            ]],
            'certificados' => [[
                'producto' => 'LECHE', 'laboratorio' => 'LAB', 'numero_certificado' => 'CERT-1',
                'fecha_emision' => '01/06/2026', 'certificado_microbiologico' => true,
                'numero_lote' => '002', 'fecha_vencimiento' => '05/12/2026',
            ]],
            'composicion' => [
                ['producto' => 'LECHE', 'insumo' => 'LECHE', 'porcentaje' => 60],
                ['producto' => 'LECHE', 'insumo' => 'AGUA', 'porcentaje' => 39.7],
            ],
            'beneficiarios' => ['rural' => $beneficiaries, 'urbana' => $beneficiaries],
            'cantidad_comites_atendidos' => 10,
            'presidente_comite_administracion' => 'PRESIDENTE',
            'representante_ministerio_salud' => 'REPRESENTANTE',
            'profesion_representante_salud' => 'MÉDICO',
        ];
    }

    private function aiOutput(): array
    {
        return ['conflictos' => [], 'datos_faltantes' => [], 'trazabilidad' => []];
    }

    private function context(): array
    {
        return ['period' => '2026-06', 'meta' => ['beneficiarios_sin_zona' => 0]];
    }
}
