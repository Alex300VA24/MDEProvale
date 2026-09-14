<?php

namespace Tests\Unit;

use App\Services\DistributionExcelService;
use PHPUnit\Framework\TestCase;

class DistributionExcelServiceTest extends TestCase
{
    public function test_it_builds_reparto_workbook_with_route_and_global_summary(): void
    {
        $club = [
            'codigo' => '001', 'nombre' => 'Club Uno', 'presidenta' => 'Presidenta',
            'direccion' => 'Dirección', 'sector' => 'Sector', 'beneficiarios' => 100,
            'leche_total' => 322, 'leche_cajas' => 6, 'leche_tarros' => 34,
            'hojuelas_kg' => 155, 'hojuelas_sacos' => 5, 'hojuelas_kilos' => 5,
        ];
        $report = [
            'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
            'vueltas' => [[
                'vuelta' => 1, 'clubs' => collect([$club]), 'total_beneficiarios' => 100,
                'total_leche' => 322, 'leche_cajas' => 6, 'leche_tarros' => 34,
                'total_hojuelas' => 155, 'hojuelas_sacos' => 5, 'hojuelas_kilos' => 5,
            ]],
            'total_beneficiarios' => 100, 'total_leche_tarros' => 322,
            'total_leche_cajas' => 6, 'total_leche_sueltos' => 34,
            'total_hojuelas_kg' => 155, 'total_hojuelas_sacos' => 5,
            'total_hojuelas_sueltos' => 5,
        ];

        $spreadsheet = (new DistributionExcelService())->build($report, 'reparto');

        $this->assertSame('Reparto por vueltas', $spreadsheet->getActiveSheet()->getTitle());
        $this->assertSame('REPARTO LOGÍSTICO POR VUELTAS - PROGRAMA VASO DE LECHE', $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertStringContainsString('VUELTA N° 1', $spreadsheet->getActiveSheet()->getCell('A3')->getValue());
    }
}
