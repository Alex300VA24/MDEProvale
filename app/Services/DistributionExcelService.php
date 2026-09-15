<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
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

        $sheet->freezePane('A6');
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.2)->setRight(0.2);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 3);

        return $spreadsheet;
    }

    private function title(Worksheet $sheet, string $title, array $report, int $columns): int
    {
        $last = $this->column($columns);
        $titleEnd = $this->column($columns - 2);
        $metaStart = $this->column($columns - 1);
        $issuedAt = $report['issued_at'] ?? null;
        $issuedDate = $issuedAt instanceof \DateTimeInterface
            ? $issuedAt->format('d/m/Y')
            : date('d/m/Y');
        $issuedTime = $issuedAt instanceof \DateTimeInterface
            ? $issuedAt->format('H:i:s')
            : date('H:i:s');

        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:D1')->setCellValue('C1', 'MUNICIPALIDAD DISTRITAL');
        $sheet->mergeCells('C2:D2')->setCellValue('C2', 'DE LA ESPERANZA');
        $sheet->mergeCells('C3:D3')->setCellValue('C3', 'O.F. Vaso de Leche');
        $sheet->mergeCells("E1:{$titleEnd}2")->setCellValue('E1', $title);
        $sheet->mergeCells("E3:{$titleEnd}3")->setCellValue(
            'E3',
            'PERÍODO ' . date('d/m/Y', strtotime($report['start_date'])) . ' - ' . date('d/m/Y', strtotime($report['end_date']))
        );
        $sheet->mergeCells("{$metaStart}1:{$last}1")->setCellValue("{$metaStart}1", 'FECHA: ' . $issuedDate);
        $sheet->mergeCells("{$metaStart}2:{$last}2")->setCellValue("{$metaStart}2", 'HORA: ' . $issuedTime);
        $sheet->mergeCells("{$metaStart}3:{$last}3");

        $logoPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'muni2.png';
        if (is_file($logoPath)) {
            $logo = new Drawing();
            $logo->setName('Logo Municipalidad Distrital de La Esperanza');
            $logo->setPath($logoPath);
            $logo->setHeight(48);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(8);
            $logo->setOffsetY(3);
            $logo->setWorksheet($sheet);
        }

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(19);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->getStyle("A1:{$last}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('C1:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C1:D2')->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle("E1:{$titleEnd}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("{$metaStart}1:{$last}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("{$metaStart}1:{$last}2")->getFont()->setBold(true)->setSize(8);

        return 5;
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
        $sheet->getStyle("A5:{$last}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFB7C4C7');
        $sheet->getStyle("A5:{$last}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
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
