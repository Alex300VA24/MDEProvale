<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReparticionController;
use Illuminate\Support\Facades\Route;

// ==================== MODULO MOVIMIENTOS ====================
Route::middleware('module:movimientos')->group(function () {
    Route::get('movimientos', [TransactionController::class, 'index'])->name('movimientos.index');
    Route::post('movimientos', [TransactionController::class, 'store'])->name('movimientos.store');
    Route::put('movimientos/{transaction}', [TransactionController::class, 'update'])->name('movimientos.update');
    Route::delete('movimientos/{transaction}', [TransactionController::class, 'destroy'])->name('movimientos.destroy');
    Route::get('movimientos-reparticion', [ReparticionController::class, 'pdf'])->name('movimientos.reparticion');
    Route::get('movimientos-reparticion-tabla', [ReparticionController::class, 'index'])->name('movimientos.reparticion-tabla');
});
