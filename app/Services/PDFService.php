<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\PDF;

class PDFService
{
    public function generate(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);
        return $pdf;
    }

    public function stream(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->generate($view, $data, $paper, $orientation)->stream($filename);
    }

    public function download(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->generate($view, $data, $paper, $orientation)->download($filename);
    }
}