<?php

use App\Http\Controllers\ClubReconocimientosController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: CLUB DE MADRES Y RECONOCIMIENTOS ====================
Route::prefix('club-reconocimientos')->name('club-reconocimientos.')->middleware('module:club-madres')->group(function () {
    Route::get('club-padron', [ClubReconocimientosController::class, 'generarPadronClub'])->name('club.padron');
    Route::get('acta-renuncia', [ClubReconocimientosController::class, 'actaRenuncia'])->name('acta-renuncia');
    Route::get('acta-reuniones', [ClubReconocimientosController::class, 'actaReuniones'])->name('acta-reuniones');
    Route::get('acta-supervision', [ClubReconocimientosController::class, 'actaSupervision'])->name('acta-supervision');

    // Resolución externa (portal de transparencia de la Municipalidad)
    Route::get('resoluciones/{resolution}/externa/buscar', [ClubReconocimientosController::class, 'buscarResolucionExterna'])->name('reconocimientos.externa.buscar');
    Route::get('resoluciones/{resolution}/externa/preview', [ClubReconocimientosController::class, 'previewResolucionExterna'])->name('reconocimientos.externa.preview');
    Route::get('resoluciones/{resolution}/externa/descargar', [ClubReconocimientosController::class, 'descargarResolucionExterna'])->name('reconocimientos.externa.descargar');
});
