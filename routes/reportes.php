<?php

use App\Http\Controllers\ReportGeneratorController;
use App\Http\Controllers\PvlReportPdfController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: REPORTES ====================
Route::prefix('reportes')->name('reportes.')->middleware('module:reportes')->group(function () {
    Route::get('generar', [ReportGeneratorController::class, 'generar'])->name('generar');

    Route::get('pvl/{pvlReportRun}/{type}/preview', [PvlReportPdfController::class, 'preview'])
        ->where('type', 'pvl|racion-a|informe')
        ->name('pvl.preview');
    Route::get('pvl/{pvlReportRun}/{type}/descargar', [PvlReportPdfController::class, 'download'])
        ->where('type', 'pvl|racion-a|informe')
        ->name('pvl.download');
});
