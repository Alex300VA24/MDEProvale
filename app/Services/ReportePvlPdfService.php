<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use InvalidArgumentException;

class ReportePvlPdfService
{
    public function __construct(private PDFService $pdfService)
    {
    }

    public function formatoPvl(array $data): PDF
    {
        return $this->pdfService->generate('reportes.pvl.formato', compact('data'), 'a4', 'landscape');
    }

    public function formatoRacionA(array $data): PDF
    {
        return $this->pdfService->generate('reportes.racion.formato', compact('data'), 'a4', 'portrait');
    }

    public function informeSustentatorio(array $data): PDF
    {
        return $this->pdfService->generate('reportes.pvl.informe', compact('data'), 'a4', 'portrait');
    }

    public function forType(string $type, array $data): PDF
    {
        return match ($type) {
            'pvl' => $this->formatoPvl($data),
            'racion-a' => $this->formatoRacionA($data),
            'informe' => $this->informeSustentatorio($data),
            default => throw new InvalidArgumentException('Tipo de reporte PDF no válido.'),
        };
    }
}
