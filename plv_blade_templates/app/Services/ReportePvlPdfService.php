<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ReportePvlPdfService
{
    public function formatoPvl(array $data)
    {
        return Pdf::loadView('reportes.pvl.formato', compact('data'))
            ->setPaper('a4', 'landscape');
    }

    public function formatoRacionA(array $data)
    {
        return Pdf::loadView('reportes.racion.formato', compact('data'))
            ->setPaper('a4', 'portrait');
    }
}
