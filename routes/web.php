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
    return view('welcome');
});

Route::middleware('auth')->group(function () {
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// ==================== MÓDULO: SOCIOS Y BENEFICIARIOS ====================
Route::prefix('socios-beneficiarios')->name('socios-beneficiarios.')->group(function () {
    // Índice principal - muestra lista de socios con sus beneficiarios
    Route::get('/', [SociosBeneficiariosController::class, 'index'])->name('index');

    // Personas
    Route::get('personas', [SociosBeneficiariosController::class, 'indexPersonas'])->name('personas.index');
    Route::get('personas/crear', [SociosBeneficiariosController::class, 'createPersona'])->name('personas.create');
    Route::post('personas', [SociosBeneficiariosController::class, 'storePersona'])->name('personas.store');
    Route::get('personas/{person}', [SociosBeneficiariosController::class, 'showPersona'])->name('personas.show');
    Route::get('personas/{person}/editar', [SociosBeneficiariosController::class, 'editPersona'])->name('personas.edit');
    Route::put('personas/{person}', [SociosBeneficiariosController::class, 'updatePersona'])->name('personas.update');
    Route::delete('personas/{person}', [SociosBeneficiariosController::class, 'destroyPersona'])->name('personas.destroy');
    Route::post('personas/ajax', [SociosBeneficiariosController::class, 'storePersonAjax'])->name('personas.store-ajax');

    // Socios
    Route::get('socios', [SociosBeneficiariosController::class, 'indexSocios'])->name('socios.index');
    Route::get('socios/crear', [SociosBeneficiariosController::class, 'createSocio'])->name('socios.create');
    Route::post('socios', [SociosBeneficiariosController::class, 'storeSocio'])->name('socios.store');
    Route::get('socios/{partner}', [SociosBeneficiariosController::class, 'showSocio'])->name('socios.show');
    Route::get('socios/{partner}/editar', [SociosBeneficiariosController::class, 'editSocio'])->name('socios.edit');
    Route::put('socios/{partner}', [SociosBeneficiariosController::class, 'updateSocio'])->name('socios.update');
    Route::delete('socios/{partner}', [SociosBeneficiariosController::class, 'destroySocio'])->name('socios.destroy');
    Route::get('socios-reportes', [SociosBeneficiariosController::class, 'reportesSocios'])->name('socios.reportes');
    Route::get('socios-reporte/{tipo}', [SociosBeneficiariosController::class, 'generarReporteSocios'])->name('socios.generar-reporte');

    // Beneficiarios
    Route::get('beneficiarios', [SociosBeneficiariosController::class, 'indexBeneficiarios'])->name('beneficiarios.index');
    Route::get('beneficiarios/crear', [SociosBeneficiariosController::class, 'createBeneficiario'])->name('beneficiarios.create');
    Route::post('beneficiarios', [SociosBeneficiariosController::class, 'storeBeneficiario'])->name('beneficiarios.store');
    Route::get('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'showBeneficiario'])->name('beneficiarios.show');
    Route::get('beneficiarios/{beneficiarie}/editar', [SociosBeneficiariosController::class, 'editBeneficiario'])->name('beneficiarios.edit');
    Route::put('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'updateBeneficiario'])->name('beneficiarios.update');
    Route::delete('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'destroyBeneficiario'])->name('beneficiarios.destroy');
    Route::get('beneficiarios-reportes', [SociosBeneficiariosController::class, 'reportesBeneficiarios'])->name('beneficiarios.reportes');
    Route::get('beneficiarios-reporte/{tipo}', [SociosBeneficiariosController::class, 'generarReporteBeneficiarios'])->name('beneficiarios.generar-reporte');
    Route::get('beneficiarios-imprimir', [SociosBeneficiariosController::class, 'imprimirFichaBeneficiario'])->name('beneficiarios.imprimir');
    Route::get('beneficiarios-padron', [SociosBeneficiariosController::class, 'reportePadronBeneficiarios'])->name('beneficiarios.padron');
});

// ==================== MÓDULO: CLUB DE MADRES Y RECONOCIMIENTOS ====================
Route::prefix('club-reconocimientos')->name('club-reconocimientos.')->group(function () {
    // Índice principal
    Route::get('/', [ClubReconocimientosController::class, 'index'])->name('index');

    // Club de Madres
    Route::get('club/crear', [ClubReconocimientosController::class, 'createClub'])->name('create');
    Route::post('club', [ClubReconocimientosController::class, 'storeClub'])->name('store');
    Route::get('club/{association}', [ClubReconocimientosController::class, 'showClub'])->name('show');
    Route::get('club/{association}/editar', [ClubReconocimientosController::class, 'editClub'])->name('edit');
    Route::put('club/{association}', [ClubReconocimientosController::class, 'updateClub'])->name('update');
    Route::delete('club/{association}', [ClubReconocimientosController::class, 'destroyClub'])->name('destroy');
    Route::get('club-reportes', [ClubReconocimientosController::class, 'reportesClub'])->name('club.reportes');
    Route::get('club-reporte/{tipo}', [ClubReconocimientosController::class, 'generarReporteClub'])->name('club.generar-reporte');
    Route::get('club-padron', [ClubReconocimientosController::class, 'generarPadronClub'])->name('club.padron');

    // Reconocimientos
    Route::get('reconocimientos', [ClubReconocimientosController::class, 'indexReconocimientos'])->name('reconocimientos.index');
    Route::get('reconocimientos/crear', [ClubReconocimientosController::class, 'createReconocimiento'])->name('reconocimientos.create');
    Route::post('reconocimientos', [ClubReconocimientosController::class, 'storeReconocimiento'])->name('reconocimientos.store');
    Route::get('reconocimientos/{resolution}', [ClubReconocimientosController::class, 'showReconocimiento'])->name('reconocimientos.show');
    Route::get('reconocimientos/{resolution}/editar', [ClubReconocimientosController::class, 'editReconocimiento'])->name('reconocimientos.edit');
    Route::put('reconocimientos/{resolution}', [ClubReconocimientosController::class, 'updateReconocimiento'])->name('reconocimientos.update');
    Route::delete('reconocimientos/{resolution}', [ClubReconocimientosController::class, 'destroyReconocimiento'])->name('reconocimientos.destroy');
    Route::get('reconocimientos-reportes', [ClubReconocimientosController::class, 'reportesReconocimientos'])->name('reconocimientos.reportes');
    Route::get('reconocimientos-reporte/{tipo}', [ClubReconocimientosController::class, 'generarReporteReconocimientos'])->name('reconocimientos.generar-reporte');

    // Asignar presidenta al comité
    Route::post('club/{association}/asignar-presidenta', [ClubReconocimientosController::class, 'asignarPresidenta'])->name('club.asignar-presidenta');
});

// ==================== MÓDULO: PRODUCTOS Y PECOSAS ====================
Route::prefix('productos-pecosas')->name('productos-pecosas.')->group(function () {
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
});

// ==================== OTROS MÓDULOS ====================
Route::resource('movimientos', TransactionController::class)->parameters([
    'movimientos' => 'transaction'
]);
Route::get('movimientos-reportes', [TransactionController::class, 'reportes'])->name('movimientos.reportes');
Route::get('movimientos-reporte/{tipo}', [TransactionController::class, 'generarReporte'])->name('movimientos.generar-reporte');
Route::get('movimientos-comprobante-salida', [TransactionController::class, 'generarComprobanteSalida'])->name('movimientos.comprobante-salida');

Route::get('sistema', [SistemaController::class, 'index'])->name('sistema.index');
Route::get('sistema/usuarios', [SistemaController::class, 'usuarios'])->name('sistema.usuarios');
Route::post('sistema/usuarios', [SistemaController::class, 'storeUsuario'])->name('sistema.usuarios.store');
Route::put('sistema/usuarios/{usuario}', [SistemaController::class, 'updateUsuario'])->name('sistema.usuarios.update');
Route::delete('sistema/usuarios/{usuario}', [SistemaController::class, 'destroyUsuario'])->name('sistema.usuarios.destroy');

Route::get('sistema/roles', [SistemaController::class, 'roles'])->name('sistema.roles');
Route::post('sistema/roles', [SistemaController::class, 'storeRol'])->name('sistema.roles.store');
Route::put('sistema/roles/{rol}', [SistemaController::class, 'updateRol'])->name('sistema.roles.update');
Route::delete('sistema/roles/{rol}', [SistemaController::class, 'destroyRol'])->name('sistema.roles.destroy');

Route::get('sistema/modulos', [SistemaController::class, 'modulos'])->name('sistema.modulos');
Route::post('sistema/modulos', [SistemaController::class, 'storeModulo'])->name('sistema.modulos.store');
Route::put('sistema/modulos/{modulo}', [SistemaController::class, 'updateModulo'])->name('sistema.modulos.update');
Route::delete('sistema/modulos/{modulo}', [SistemaController::class, 'destroyModulo'])->name('sistema.modulos.destroy');

Route::get('mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index');
});

require __DIR__ . '/auth.php';
