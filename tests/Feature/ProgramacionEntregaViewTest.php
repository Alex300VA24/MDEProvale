<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ProgramacionEntregaViewTest extends TestCase
{
    public function test_roster_uses_selected_period_requested_columns_and_totals(): void
    {
        $html = View::make('programacion_entrega', [
            'mes' => 2,
            'anio' => 2026,
            'sector' => 'SECTOR 1',
            'clubes' => [[
                'codigo' => '010',
                'nombre' => 'CLUB DEMO',
                'presidenta' => 'PRESIDENTA DEMO',
                'direccion' => 'CALLE DEMO',
                'sector' => 'SECTOR 1',
                'total_beneficiarios' => 10,
                'leche' => 30.05,
                'leche_redondeo' => 30,
                'hojuela' => 14.42,
                'hojuela_redondeo' => 14,
            ]],
        ])->render();

        $this->assertStringContainsString('PROGRAMACIÓN DE ENTREGA DE LOS PRODUCTOS DEL PROGRAMA VASO DE LECHE', $html);
        $this->assertStringContainsString('(PERIODO DEL 01 AL 28 FEBRERO 2026)', $html);
        $this->assertStringContainsString('LECHE EVAPORADA ENTERA Y HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES', $html);
        $this->assertStringContainsString('BENEF<br>(TOTAL)', $html);
        $this->assertStringContainsString('LECHE AL<br>REDONDEO', $html);
        $this->assertStringContainsString('HOJUELA AL<br>REDONDEO', $html);
        $this->assertStringContainsString('<td colspan="6" style="text-align: right; padding-right: 6px;">TOTAL</td>', $html);
        $this->assertStringContainsString('30.05', $html);
        $this->assertStringContainsString('14.42', $html);
    }
}
