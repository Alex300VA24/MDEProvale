<?php

use App\Http\Controllers\ResponsablesRacionesController;
use Illuminate\Support\Facades\Route;

// ==================== MODULO RESPONSABLES Y RACIONES ====================
Route::get('responsables-raciones', [ResponsablesRacionesController::class, 'index'])->name('responsables-raciones.index')->middleware('module:responsables-raciones');
Route::post('responsables-raciones/responsibles/{type}', [ResponsablesRacionesController::class, 'updateResponsible'])->name('responsables-raciones.responsibles.update')->middleware('module:responsables-raciones');
Route::post('responsables-raciones/raciones', [ResponsablesRacionesController::class, 'storeRacion'])->name('responsables-raciones.raciones.store')->middleware('module:responsables-raciones');
Route::put('responsables-raciones/raciones/{id}', [ResponsablesRacionesController::class, 'updateRacion'])->name('responsables-raciones.raciones.update')->middleware('module:responsables-raciones');
Route::delete('responsables-raciones/raciones/{id}', [ResponsablesRacionesController::class, 'deleteRacion'])->name('responsables-raciones.raciones.destroy')->middleware('module:responsables-raciones');
