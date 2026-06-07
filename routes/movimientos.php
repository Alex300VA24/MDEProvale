<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ==================== MODULO MOVIMIENTOS ====================
Route::resource('movimientos', TransactionController::class)->middleware('module:movimientos')->parameters([
    'movimientos' => 'transaction'
]);
Route::get('movimientos-reportes', [TransactionController::class, 'reportes'])->name('movimientos.reportes')->middleware('module:movimientos');
Route::get('movimientos-reporte/{tipo}', [TransactionController::class, 'generarReporte'])->name('movimientos.generar-reporte')->middleware('module:movimientos');
Route::get('movimientos-reparticion', [TransactionController::class, 'reparticion'])->name('movimientos.reparticion')->middleware('module:movimientos');
Route::get('movimientos-reparticion-tabla', [TransactionController::class, 'reparticionTabla'])->name('movimientos.reparticion-tabla')->middleware('module:movimientos');
