<?php

use App\Http\Controllers\MantenimientoController;
use Illuminate\Support\Facades\Route;

// ==================== MODULO MANTENIMIENTO ====================
Route::get('mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index')->middleware('module:mantenimiento');
Route::post('mantenimiento/responsibles/{type}', [MantenimientoController::class, 'updateResponsible'])->name('mantenimiento.responsibles.update')->middleware('module:mantenimiento');
Route::post('mantenimiento/raciones', [MantenimientoController::class, 'storeRacion'])->name('mantenimiento.raciones.store')->middleware('module:mantenimiento');
Route::put('mantenimiento/raciones/{id}', [MantenimientoController::class, 'updateRacion'])->name('mantenimiento.raciones.update')->middleware('module:mantenimiento');
Route::delete('mantenimiento/raciones/{id}', [MantenimientoController::class, 'deleteRacion'])->name('mantenimiento.raciones.destroy')->middleware('module:mantenimiento');
