<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DistributionExcelService
{
    public function build(array $report, string $document): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(match ($document) {
            'cargo' => 'Cargo general',
            'fiscalizacion' => 'Fiscalización',
            default => 'Reparto por vueltas',
        });

        $spreadsheet->getProperties()
            ->setCreator('Municipalidad Distrital de La Esperanza')
            ->setTitle('Programa Vaso de Leche - ' . $sheet->getTitle());

        match ($document) {
            'cargo' => $this->cargo($sheet, $report),
            'fiscalizacion' => $this->fiscalizacion($sheet, $report),
            default => $this->reparto($sheet, $report),
        };

        $sheet->freezePane('A4');
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.2)->setRight(0.2);

        return $spreadsheet;
    }

    private function title(Worksheet $sheet, string $title, array $report, int $columns): int
    {
        $last = $this->column($columns);
        $sheet->mergeCells("A1:{$last}1")->setCellValue('A1', $title);
        $sheet->mergeCells("A2:{$last}2")->setCellValue(
            'A2',
            'Período: ' . date('d/m/Y', strtotime($report['start_date'])) . ' - ' . date('d/m/Y', strtotime($report['end_date']))
        );
        $sheet->getStyle("A1:{$last}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        return 3;
    }

    private function cargo(Worksheet $sheet, array $report): void
    {
        $headers = ['N°', 'CÓDIGO', 'CLUB DE MADRES', 'PRESIDENTA', 'DIRECCIÓN', 'SECTOR', 'BENEFICIARIOS', 'TOTAL LECHE (TARROS)', 'TOTAL HOJUELA (KG)', 'FIRMA'];
        $row = $this->title($sheet, 'CARGO GENERAL DE ENTREGA DE PRODUCTOS - PROGRAMA VASO DE LECHE', $report, count($headers));
        $this->headers($sheet, $row++, $headers);
        foreach ($report['associations'] as $index => $club) {
            $sheet->fromArray([$index + 1, $club['codigo'], $club['nombre'], $club['presidenta'], $club['direccion'], $club['sector'], $club['beneficiarios'], $club['leche_total'], $club['hojuelas_kg'], ''], null, "A{$row}");
            $row++;
        }
        $sheet->fromArray(['', '', '', '', '', 'TOTALES', $report['total_beneficiarios'], $report['total_leche_tarros'], $report['total_hojuelas_kg'], ''], null, "A{$row}");
        $this->finish($sheet, 10, $row, [5, 12, 28, 24, 28, 18, 14, 18, 18, 22]);
    }

    private function fiscalizacion(Worksheet $sheet, array $report): void
    {
        $headers = ['N°', 'CÓDIGO', 'CLUB DE MADRES', 'PRESIDENTA', 'SECTOR', 'BENEFICIARIOS', 'TOTAL LECHE', 'TOTAL HOJUELA', 'RACIÓN LECHE/DÍA', 'RACIÓN HOJ./DÍA', 'OBSERVACIONES'];
        $row = $this->title($sheet, 'CONTROL Y FISCALIZACIÓN - RACIÓN POR DÍA', $report, count($headers));
        $this->headers($sheet, $row++, $headers);
        foreach ($report['associations'] as $index => $club) {
            $sheet->fromArray([$index + 1, $club['codigo'], $club['nombre'], $club['presidenta'], $club['sector'], $club['beneficiarios'], $club['leche_total'], $club['hojuelas_kg'], $club['racion_diaria_leche'], $club['racion_diaria_hojuelas'], $club['observacion']], null, "A{$row}");
            $sheet->getStyle("I{$row}:J{$row}")->getNumberFormat()->setFormatCode('0.00');
            $row++;
        }
        $this->finish($sheet, 11, $row - 1, [5, 12, 28, 24, 18, 14, 14, 14, 18, 18, 28]);
    }

    private function reparto(Worksheet $sheet, array $report): void
    {
        $headers = ['N°', 'COD', 'CLUB DE MADRES', 'PRESIDENTA', 'DIRECCIÓN', 'SECTOR', 'BENEF.', 'LECHE', 'CAJAS', 'TARROS', 'HOJUELA', 'SACOS', 'KILOS'];
        $row = $this->title($sheet, 'REPARTO LOGÍSTICO POR VUELTAS - PROGRAMA VASO DE LECHE', $report, count($headers));
        foreach ($report['vueltas'] as $routeIndex => $route) {
            if ($routeIndex > 0) {
                $sheet->setBreak('A' . $row, Worksheet::BREAK_ROW);
            }
            $sheet->mergeCells("A{$row}:M{$row}")->setCellValue("A{$row}", 'VUELTA N° ' . $route['vuelta']);
            $sheet->getStyle("A{$row}:M{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF174A68');
            $row++;
            $this->headers($sheet, $row++, $headers);
            foreach ($route['clubs'] as $index => $club) {
                $sheet->fromArray([$index + 1, $club['codigo'], $club['nombre'], $club['presidenta'], $club['direccion'], $club['sector'], $club['beneficiarios'], $club['leche_total'], $club['leche_cajas'], $club['leche_tarros'], $club['hojuelas_kg'], $club['hojuelas_sacos'], $club['hojuelas_kilos']], null, "A{$row}");
                $row++;
            }
            $sheet->fromArray(['', '', '', '', '', 'SUBTOTAL', $route['total_beneficiarios'], $route['total_leche'], $route['leche_cajas'], $route['leche_tarros'], $route['total_hojuelas'], $route['hojuelas_sacos'], $route['hojuelas_kilos']], null, "A{$row}");
            $sheet->getStyle("A{$row}:M{$row}")->getFont()->setBold(true);
            $row += 2;
        }
        $sheet->mergeCells("A{$row}:F{$row}")->setCellValue("A{$row}", 'RESUMEN FINAL DE CARGA');
        $sheet->fromArray([$report['total_beneficiarios'], $report['total_leche_tarros'], $report['total_leche_cajas'], $report['total_leche_sueltos'], $report['total_hojuelas_kg'], $report['total_hojuelas_sacos'], $report['total_hojuelas_sueltos']], null, "G{$row}");
        $sheet->getStyle("A{$row}:M{$row}")->getFont()->setBold(true);
        $this->finish($sheet, 13, $row, [5, 10, 28, 24, 28, 18, 10, 10, 10, 10, 11, 10, 10]);
    }

    private function headers(Worksheet $sheet, int $row, array $headers): void
    {
        $sheet->fromArray($headers, null, "A{$row}");
        $last = $this->column(count($headers));
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF256B73');
        $sheet->getStyle("A{$row}:{$last}{$row}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function finish(Worksheet $sheet, int $columns, int $lastRow, array $widths): void
    {
        $last = $this->column($columns);
        $sheet->getStyle("A3:{$last}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFB7C4C7');
        $sheet->getStyle("A3:{$last}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension($this->column($index + 1))->setWidth($width);
        }
        $sheet->getStyle("A1:{$last}{$lastRow}")->getFont()->setName('Arial')->setSize(9);
        $sheet->getHeaderFooter()->setOddFooter('&C Página &P de &N');
    }

    private function column(int $number): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($number);
    }
}
