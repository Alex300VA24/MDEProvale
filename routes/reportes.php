<?php

use App\Http\Controllers\ReportGeneratorController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: REPORTES ====================
Route::prefix('reportes')->name('reportes.')->middleware('module:reportes')->group(function () {
    Route::get('generar', [ReportGeneratorController::class, 'generar'])->name('generar');
});
