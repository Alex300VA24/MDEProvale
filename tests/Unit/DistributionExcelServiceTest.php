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
            'issued_at' => new \DateTimeImmutable('2026-09-14 10:11:12'),
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
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Reparto por vueltas', $sheet->getTitle());
        $this->assertSame('MUNICIPALIDAD DISTRITAL', $sheet->getCell('C1')->getValue());
        $this->assertSame('REPARTO LOGÍSTICO POR VUELTAS - PROGRAMA VASO DE LECHE', $sheet->getCell('E1')->getValue());
        $this->assertSame('PERÍODO 01/09/2026 - 30/09/2026', $sheet->getCell('E3')->getValue());
        $this->assertSame('FECHA: 14/09/2026', $sheet->getCell('L1')->getValue());
        $this->assertSame('HORA: 10:11:12', $sheet->getCell('L2')->getValue());
        $this->assertStringContainsString('VUELTA N° 1', $sheet->getCell('A5')->getValue());
        $this->assertCount(1, $sheet->getDrawingCollection());
    }
}
