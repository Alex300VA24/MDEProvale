<?php

use App\Http\Controllers\ClubReconocimientosController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: CLUB DE MADRES Y RECONOCIMIENTOS ====================
Route::prefix('club-reconocimientos')->name('club-reconocimientos.')->middleware('module:club-madres')->group(function () {
    // Índice principal
    Route::get('/', [ClubReconocimientosController::class, 'index'])->name('index');

    // Club de Madres
    Route::post('club', [ClubReconocimientosController::class, 'storeClub'])->name('store');
    Route::put('club/{association}', [ClubReconocimientosController::class, 'updateClub'])->name('update');
    Route::delete('club/{association}', [ClubReconocimientosController::class, 'destroyClub'])->name('destroy');
    Route::get('club-padron', [ClubReconocimientosController::class, 'generarPadronClub'])->name('club.padron');

    // Reconocimientos
    Route::get('reconocimientos', [ClubReconocimientosController::class, 'indexReconocimientos'])->name('reconocimientos.index');
    Route::post('reconocimientos', [ClubReconocimientosController::class, 'storeReconocimiento'])->name('reconocimientos.store');
    Route::put('reconocimientos/{resolution}', [ClubReconocimientosController::class, 'updateReconocimiento'])->name('reconocimientos.update');
    Route::delete('reconocimientos/{resolution}', [ClubReconocimientosController::class, 'destroyReconocimiento'])->name('reconocimientos.destroy');

    // Asignar presidenta al comité
    Route::post('club/{association}/asignar-presidenta', [ClubReconocimientosController::class, 'asignarPresidenta'])->name('club.asignar-presidenta');

    // Resolución externa (portal de transparencia de la Municipalidad)
    Route::get('resoluciones/{resolution}/externa/buscar', [ClubReconocimientosController::class, 'buscarResolucionExterna'])->name('reconocimientos.externa.buscar');
    Route::get('resoluciones/{resolution}/externa/preview', [ClubReconocimientosController::class, 'previewResolucionExterna'])->name('reconocimientos.externa.preview');
    Route::get('resoluciones/{resolution}/externa/descargar', [ClubReconocimientosController::class, 'descargarResolucionExterna'])->name('reconocimientos.externa.descargar');
});
