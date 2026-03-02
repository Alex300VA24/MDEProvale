<?php

use App\Http\Controllers\AssociationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\BeneficiarieController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PecosaController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::resource('club-de-madres', AssociationController::class)->parameters([
    'club-de-madres' => 'association'
]);
Route::get('club-de-madres-reportes', [AssociationController::class, 'reportes'])->name('club-de-madres.reportes');
Route::get('club-de-madres-reporte/{tipo}', [AssociationController::class, 'generarReporte'])->name('club-de-madres.generar-reporte');

Route::resource('socios', PartnerController::class)->parameters([
    'socios' => 'partner'
]);
Route::get('socios-reportes', [PartnerController::class, 'reportes'])->name('socios.reportes');
Route::get('socios-reporte/{tipo}', [PartnerController::class, 'generarReporte'])->name('socios.generar-reporte');

Route::resource('beneficiarios', BeneficiarieController::class)->parameters([
    'beneficiarios' => 'beneficiarie'
]);
Route::get('beneficiarios-reportes', [BeneficiarieController::class, 'reportes'])->name('beneficiarios.reportes');
Route::get('beneficiarios-reporte/{tipo}', [BeneficiarieController::class, 'generarReporte'])->name('beneficiarios.generar-reporte');

Route::resource('premios', AwardController::class)->parameters([
    'premios' => 'award'
]);
Route::get('premios-reportes', [AwardController::class, 'reportes'])->name('premios.reportes');
Route::get('premios-reporte/{tipo}', [AwardController::class, 'generarReporte'])->name('premios.generar-reporte');

Route::resource('productos', ProductController::class)->parameters([
    'productos' => 'product'
]);
Route::get('productos-reportes', [ProductController::class, 'reportes'])->name('productos.reportes');
Route::get('productos-reporte/{tipo}', [ProductController::class, 'generarReporte'])->name('productos.generar-reporte');

Route::resource('movimientos', TransactionController::class)->parameters([
    'movimientos' => 'transaction'
]);
Route::get('movimientos-reportes', [TransactionController::class, 'reportes'])->name('movimientos.reportes');
Route::get('movimientos-reporte/{tipo}', [TransactionController::class, 'generarReporte'])->name('movimientos.generar-reporte');

Route::resource('pecosas', PecosaController::class)->parameters([
    'pecosas' => 'pecosa'
]);
Route::get('pecosas-reportes', [PecosaController::class, 'reportes'])->name('pecosas.reportes');
Route::get('pecosas-reporte/{tipo}', [PecosaController::class, 'generarReporte'])->name('pecosas.generar-reporte');
Route::get('sistema', [SistemaController::class, 'index'])->name('sistema.index');
Route::get('mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index');

require __DIR__.'/auth.php';
