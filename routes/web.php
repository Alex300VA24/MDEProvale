<?php

use App\Http\Controllers\SociosBeneficiariosController;
use App\Http\Controllers\ClubReconocimientosController;
use App\Http\Controllers\ProductosPecosasController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\MantenimientoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login');
});

// Ruta pública para refrescar CSRF token
Route::get('/refresh-csrf', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->middleware('web');

// Rutas públicas para recuperación de contraseña (SIN autenticación)
Route::post('password-reset-request', [SistemaController::class, 'requestPasswordReset'])->name('password-reset-request');

// Rutas que requieren autenticación
Route::middleware('auth')->group(function () {
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// ==================== MÓDULO: SOCIOS Y BENEFICIARIOS ====================
Route::prefix('socios-beneficiarios')->name('socios-beneficiarios.')->middleware('module:socios-beneficiarios')->group(function () {
    // Índice principal - muestra lista de socios con sus beneficiarios
    Route::get('/', [SociosBeneficiariosController::class, 'index'])->name('index');

    // Personas
    Route::get('personas', [SociosBeneficiariosController::class, 'indexPersonas'])->name('personas.index');
    Route::post('personas', [SociosBeneficiariosController::class, 'storePersona'])->name('personas.store');
    Route::put('personas/{person}', [SociosBeneficiariosController::class, 'updatePersona'])->name('personas.update');
    Route::delete('personas/{person}', [SociosBeneficiariosController::class, 'destroyPersona'])->name('personas.destroy');

    // Socios
    Route::post('socios', [SociosBeneficiariosController::class, 'storeSocio'])->name('socios.store');
    Route::put('socios/{partner}', [SociosBeneficiariosController::class, 'updateSocio'])->name('socios.update');
    Route::delete('socios/{partner}', [SociosBeneficiariosController::class, 'destroySocio'])->name('socios.destroy');

    // Beneficiarios
    Route::get('beneficiarios', [SociosBeneficiariosController::class, 'indexBeneficiarios'])->name('beneficiarios.index');
    Route::get('beneficiarios-imprimir', [SociosBeneficiariosController::class, 'imprimirFichaBeneficiario'])->name('beneficiarios.imprimir');
    Route::get('beneficiarios-padron', [SociosBeneficiariosController::class, 'reportePadronBeneficiarios'])->name('beneficiarios.padron');
});

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
});

// ==================== MÓDULO: PRODUCTOS Y PECOSAS ====================
Route::prefix('productos-pecosas')->name('productos-pecosas.')->middleware('module:productos')->group(function () {
    // Índice principal
    Route::get('/', [ProductosPecosasController::class, 'index'])->name('index');

    // Productos
    Route::get('productos', [ProductosPecosasController::class, 'indexProductos'])->name('productos.index');
    Route::get('productos/crear', [ProductosPecosasController::class, 'createProducto'])->name('productos.create');
    Route::post('productos', [ProductosPecosasController::class, 'storeProducto'])->name('productos.store');
    Route::post('productos/ajax', [ProductosPecosasController::class, 'storeProductoAjax'])->name('productos.store-ajax');
    Route::get('productos/{product}', [ProductosPecosasController::class, 'showProducto'])->name('productos.show');
    Route::get('productos/{product}/editar', [ProductosPecosasController::class, 'editProducto'])->name('productos.edit');
    Route::put('productos/{product}', [ProductosPecosasController::class, 'updateProducto'])->name('productos.update');
    Route::delete('productos/{product}', [ProductosPecosasController::class, 'destroyProducto'])->name('productos.destroy');
    Route::get('productos-reportes', [ProductosPecosasController::class, 'reportesProductos'])->name('productos.reportes');
    Route::get('productos-reporte/{tipo}', [ProductosPecosasController::class, 'generarReporteProductos'])->name('productos.generar-reporte');

    // Pecosas
    Route::get('pecosas', [ProductosPecosasController::class, 'indexPecosas'])->name('pecosas.index');
    Route::get('pecosas/crear', [ProductosPecosasController::class, 'createPecosa'])->name('pecosas.create');
    Route::post('pecosas', [ProductosPecosasController::class, 'storePecosa'])->name('pecosas.store');
    Route::get('pecosas/{pecosa}/comprobante', [ProductosPecosasController::class, 'generarComprobante'])->name('pecosas.comprobante');
    Route::get('pecosas/{pecosa}', [ProductosPecosasController::class, 'showPecosa'])->name('pecosas.show');
    Route::get('pecosas/{pecosa}/editar', [ProductosPecosasController::class, 'editPecosa'])->name('pecosas.edit');
    Route::put('pecosas/{pecosa}', [ProductosPecosasController::class, 'updatePecosa'])->name('pecosas.update');
    Route::delete('pecosas/{pecosa}', [ProductosPecosasController::class, 'destroyPecosa'])->name('pecosas.destroy');
    Route::get('pecosas-reportes', [ProductosPecosasController::class, 'reportesPecosas'])->name('pecosas.reportes');
    Route::get('pecosas-reporte/{tipo}', [ProductosPecosasController::class, 'generarReportePecosas'])->name('pecosas.generar-reporte');
    Route::get('pecosas-programacion-entrega', [ProductosPecosasController::class, 'generarProgramacionEntrega'])->name('pecosas.programacion-entrega');

    // Kardex / Detalle Productos
    Route::get('productos-detalle', [ProductosPecosasController::class, 'productosDetalle'])->name('productos-detalle');
    Route::post('productos-detalle', [ProductosPecosasController::class, 'storeProductoDetalle'])->name('productos-detalle.store');
});

// ==================== MODULO MOVIMIENTOS ====================
Route::resource('movimientos', TransactionController::class)->middleware('module:movimientos')->parameters([
    'movimientos' => 'transaction'
]);
Route::get('movimientos-reportes', [TransactionController::class, 'reportes'])->name('movimientos.reportes')->middleware('module:movimientos');
Route::get('movimientos-reporte/{tipo}', [TransactionController::class, 'generarReporte'])->name('movimientos.generar-reporte')->middleware('module:movimientos');
Route::get('movimientos-reparticion', [TransactionController::class, 'reparticion'])->name('movimientos.reparticion')->middleware('module:movimientos');
Route::get('movimientos-reparticion-tabla', [TransactionController::class, 'reparticionTabla'])->name('movimientos.reparticion-tabla')->middleware('module:movimientos');


// ==================== MODULO SISTEMA ====================
Route::get('sistema', [SistemaController::class, 'index'])->name('sistema.index')->middleware('module:sistema');
Route::get('sistema/usuarios', [SistemaController::class, 'usuarios'])->name('sistema.usuarios')->middleware('module:sistema');
Route::post('sistema/usuarios', [SistemaController::class, 'storeUsuario'])->name('sistema.usuarios.store')->middleware('module:sistema');
Route::put('sistema/usuarios/{usuario}', [SistemaController::class, 'updateUsuario'])->name('sistema.usuarios.update')->middleware('module:sistema');
Route::delete('sistema/usuarios/{usuario}', [SistemaController::class, 'destroyUsuario'])->name('sistema.usuarios.destroy')->middleware('module:sistema');
Route::post('sistema/usuarios/{usuario}/reset-password', [SistemaController::class, 'resetUserPassword'])->name('sistema.usuarios.reset-password')->middleware('module:sistema');

Route::get('sistema/notifications/count/unread', [SistemaController::class, 'getUnreadNotificationsCount'])->name('sistema.notifications.count')->middleware('module:sistema');
Route::get('sistema/notifications', [SistemaController::class, 'notifications'])->name('sistema.notifications')->middleware('module:sistema');
Route::post('sistema/notifications/{notification}/approve', [SistemaController::class, 'approveNotification'])->name('sistema.notifications.approve')->middleware('module:sistema');
Route::post('sistema/notifications/{notification}/reject', [SistemaController::class, 'rejectNotification'])->name('sistema.notifications.reject')->middleware('module:sistema');
Route::post('sistema/notifications/mark-seen', [SistemaController::class, 'markAllNotificationsAsSeen'])->name('sistema.notifications.mark-seen')->middleware('module:sistema');

Route::get('sistema/roles', [SistemaController::class, 'roles'])->name('sistema.roles')->middleware('module:sistema');
Route::post('sistema/roles', [SistemaController::class, 'storeRol'])->name('sistema.roles.store')->middleware('module:sistema');
Route::put('sistema/roles/{rol}', [SistemaController::class, 'updateRol'])->name('sistema.roles.update')->middleware('module:sistema');
Route::delete('sistema/roles/{rol}', [SistemaController::class, 'destroyRol'])->name('sistema.roles.destroy')->middleware('module:sistema');

Route::get('sistema/modulos', [SistemaController::class, 'modulos'])->name('sistema.modulos')->middleware('module:sistema');
Route::post('sistema/modulos', [SistemaController::class, 'storeModulo'])->name('sistema.modulos.store')->middleware('module:sistema');
Route::put('sistema/modulos/{modulo}', [SistemaController::class, 'updateModulo'])->name('sistema.modulos.update')->middleware('module:sistema');
Route::delete('sistema/modulos/{modulo}', [SistemaController::class, 'destroyModulo'])->name('sistema.modulos.destroy')->middleware('module:sistema');

Route::get('mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index')->middleware('module:mantenimiento');
Route::post('mantenimiento/responsibles/{type}', [MantenimientoController::class, 'updateResponsible'])->name('mantenimiento.responsibles.update')->middleware('module:mantenimiento');
Route::post('mantenimiento/raciones', [MantenimientoController::class, 'storeRacion'])->name('mantenimiento.raciones.store')->middleware('module:mantenimiento');
Route::put('mantenimiento/raciones/{id}', [MantenimientoController::class, 'updateRacion'])->name('mantenimiento.raciones.update')->middleware('module:mantenimiento');
Route::delete('mantenimiento/raciones/{id}', [MantenimientoController::class, 'deleteRacion'])->name('mantenimiento.raciones.destroy')->middleware('module:mantenimiento');
});

require __DIR__ . '/auth.php';
