<?php

use App\Http\Controllers\ControlInternoController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReparticionController;
use Illuminate\Support\Facades\Route;

// ==================== REGISTRO DE INGRESO Y REPARTICIÓN ====================
// El antiguo módulo "movimientos" se dividió en dos submódulos; los nombres de ruta se conservan.

// Registro de Ingreso (slug de submódulo: registro-ingreso)
Route::middleware('module:registro-ingreso')->group(function () {
    Route::get('movimientos', [TransactionController::class, 'index'])->name('movimientos.index');
    Route::post('movimientos', [TransactionController::class, 'store'])->name('movimientos.store');
    Route::put('movimientos/{transaction}', [TransactionController::class, 'update'])->name('movimientos.update');
    Route::delete('movimientos/{transaction}', [TransactionController::class, 'destroy'])->name('movimientos.destroy');

    // POST: el payload JSON del modal excede el largo máximo de una URL (414).
    Route::post('movimientos-ficha-control', [ControlInternoController::class, 'fichaPdf'])->name('movimientos.ficha-control');
    Route::post('movimientos-tarjeta-control', [ControlInternoController::class, 'tarjetaPdf'])->name('movimientos.tarjeta-control');
});

// Repartición (slug de submódulo: reparticion)
Route::middleware('module:reparticion')->group(function () {
    Route::get('movimientos-reparticion', [ReparticionController::class, 'pdf'])->name('movimientos.reparticion');
    Route::get('movimientos-reparticion-con-firma', [ReparticionController::class, 'pdfConFirma'])->name('movimientos.reparticion-con-firma');
    Route::get('movimientos-reparticion-tabla', [ReparticionController::class, 'index'])->name('movimientos.reparticion-tabla');
    Route::get('movimientos-distribucion-exportar', [ReparticionController::class, 'export'])->name('movimientos.distribucion.export');
});
