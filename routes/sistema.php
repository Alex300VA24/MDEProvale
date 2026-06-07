<?php

use App\Http\Controllers\SistemaController;
use Illuminate\Support\Facades\Route;

// ==================== MODULO SISTEMA ====================
Route::get('sistema', [SistemaController::class, 'index'])->name('sistema.index')->middleware('module:sistema');
Route::get('sistema/usuarios', [SistemaController::class, 'usuarios'])->name('sistema.usuarios')->middleware('module:sistema');
Route::post('sistema/usuarios', [SistemaController::class, 'storeUsuario'])->name('sistema.usuarios.store')->middleware('module:sistema');
Route::put('sistema/usuarios/{usuario}', [SistemaController::class, 'updateUsuario'])->name('sistema.usuarios.update')->middleware('module:sistema');
Route::delete('sistema/usuarios/{usuario}', [SistemaController::class, 'destroyUsuario'])->name('sistema.usuarios.destroy')->middleware('module:sistema');
Route::post('sistema/usuarios/{usuario}/reset-password', [SistemaController::class, 'resetUserPassword'])->name('sistema.usuarios.reset-password')->middleware('module:sistema');

Route::get('sistema/notifications/count/unread', [SistemaController::class, 'getUnreadNotificationsCount'])->name('sistema.notifications.count');
Route::get('sistema/notifications', [SistemaController::class, 'notifications'])->name('sistema.notifications')->middleware('module:sistema');
Route::get('sistema/notifications/partial', [SistemaController::class, 'notificationsPartial'])->name('sistema.notifications.partial');
Route::post('sistema/notifications/{notification}/approve', [SistemaController::class, 'approveNotification'])->name('sistema.notifications.approve')->middleware('module:sistema');
Route::post('sistema/notifications/{notification}/reject', [SistemaController::class, 'rejectNotification'])->name('sistema.notifications.reject')->middleware('module:sistema');
Route::post('sistema/notifications/mark-seen', [SistemaController::class, 'markAllNotificationsAsSeen'])->name('sistema.notifications.mark-seen');

Route::get('sistema/roles', [SistemaController::class, 'roles'])->name('sistema.roles')->middleware('module:sistema');
Route::post('sistema/roles', [SistemaController::class, 'storeRol'])->name('sistema.roles.store')->middleware('module:sistema');
Route::put('sistema/roles/{rol}', [SistemaController::class, 'updateRol'])->name('sistema.roles.update')->middleware('module:sistema');
Route::delete('sistema/roles/{rol}', [SistemaController::class, 'destroyRol'])->name('sistema.roles.destroy')->middleware('module:sistema');

Route::get('sistema/modulos', [SistemaController::class, 'modulos'])->name('sistema.modulos')->middleware('module:sistema');
Route::post('sistema/modulos', [SistemaController::class, 'storeModulo'])->name('sistema.modulos.store')->middleware('module:sistema');
Route::put('sistema/modulos/{modulo}', [SistemaController::class, 'updateModulo'])->name('sistema.modulos.update')->middleware('module:sistema');
Route::delete('sistema/modulos/{modulo}', [SistemaController::class, 'destroyModulo'])->name('sistema.modulos.destroy')->middleware('module:sistema');
