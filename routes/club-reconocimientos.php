<?php

use App\Http\Controllers\ClubReconocimientosController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: COMITÉS Y RESOLUCIONES (documentos) ====================
Route::prefix('club-reconocimientos')->name('club-reconocimientos.')->group(function () {
    // Comités (slug de submódulo: club-madres)
    Route::middleware('module:club-madres')->group(function () {
        Route::get('club-padron', [ClubReconocimientosController::class, 'generarPadronClub'])->name('club.padron');
        Route::get('acta-renuncia', [ClubReconocimientosController::class, 'actaRenuncia'])->name('acta-renuncia');
        Route::get('acta-reuniones', [ClubReconocimientosController::class, 'actaReuniones'])->name('acta-reuniones');
    });

    // Supervisión de Comités (slug de módulo: supervision-comites)
    Route::get('acta-supervision', [ClubReconocimientosController::class, 'actaSupervision'])
        ->middleware('module:supervision-comites')
        ->name('acta-supervision');

    // Resoluciones (slug de submódulo: reconocimientos)
    // Resolución externa (portal de transparencia de la Municipalidad)
    Route::middleware('module:reconocimientos')->group(function () {
        Route::get('resoluciones/{resolution}/externa/buscar', [ClubReconocimientosController::class, 'buscarResolucionExterna'])->name('reconocimientos.externa.buscar');
        Route::get('resoluciones/{resolution}/externa/preview', [ClubReconocimientosController::class, 'previewResolucionExterna'])->name('reconocimientos.externa.preview');
        Route::get('resoluciones/{resolution}/externa/descargar', [ClubReconocimientosController::class, 'descargarResolucionExterna'])->name('reconocimientos.externa.descargar');
    });
});
