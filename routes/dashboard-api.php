<?php

use App\Http\Controllers\Api\ComitesController;
use App\Http\Controllers\Api\MovimientosController;
use App\Http\Controllers\Api\ProductosPecosasController;
use App\Http\Controllers\Api\SociosBeneficiariosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes
|--------------------------------------------------------------------------
|
| Endpoints JSON consumidos por el dashboard SPA. Todas las rutas viven bajo
| /api/dashboard/{modulo}, protegidas por auth:sanctum (grupo api.php) y por
| el middleware de módulo correspondiente.
|
*/

// ==================== MÓDULO: SOCIOS Y BENEFICIARIOS ====================
Route::prefix('dashboard/socios-beneficiarios')->middleware('module:socios-beneficiarios')->name('api.socios-beneficiarios.')->group(function () {
    // Socios
    Route::get('partners', [SociosBeneficiariosController::class, 'partners'])->name('partners');
    Route::get('partners/options', [SociosBeneficiariosController::class, 'partnersOptions'])->name('partners.options');
    Route::post('partners', [SociosBeneficiariosController::class, 'storePartner'])->name('partners.store');
    Route::put('partners/{partner}', [SociosBeneficiariosController::class, 'updatePartner'])->name('partners.update');
    Route::delete('partners/{partner}', [SociosBeneficiariosController::class, 'destroyPartner'])->name('partners.destroy');

    // Personas
    Route::get('personas', [SociosBeneficiariosController::class, 'personas'])->name('personas');
    Route::get('personas/options', [SociosBeneficiariosController::class, 'personasOptions'])->name('personas.options');
    Route::post('personas', [SociosBeneficiariosController::class, 'storePersona'])->name('personas.store');
    Route::put('personas/{person}', [SociosBeneficiariosController::class, 'updatePersona'])->name('personas.update');
    Route::delete('personas/{person}', [SociosBeneficiariosController::class, 'destroyPersona'])->name('personas.destroy');

    // Beneficiarios
    Route::get('beneficiarios', [SociosBeneficiariosController::class, 'beneficiarios'])->name('beneficiarios');
    Route::get('beneficiarios/options', [SociosBeneficiariosController::class, 'beneficiariosOptions'])->name('beneficiarios.options');
    Route::post('beneficiarios', [SociosBeneficiariosController::class, 'storeBeneficiario'])->name('beneficiarios.store');
    Route::put('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'updateBeneficiario'])->name('beneficiarios.update');
    Route::delete('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'destroyBeneficiario'])->name('beneficiarios.destroy');
});

// ==================== MÓDULO: COMITÉS (CLUB DE MADRES + RECONOCIMIENTOS) ====================
Route::prefix('dashboard/club-madres')->middleware('module:club-madres')->name('api.club-madres.')->group(function () {
    // Comités (Club de Madres)
    Route::get('clubs', [ComitesController::class, 'clubs'])->name('clubs');
    Route::get('clubs/options', [ComitesController::class, 'clubsOptions'])->name('clubs.options');
    Route::get('clubs/{association}', [ComitesController::class, 'club'])->name('clubs.show');
    Route::post('clubs', [ComitesController::class, 'storeClub'])->name('clubs.store');
    Route::put('clubs/{association}', [ComitesController::class, 'updateClub'])->name('clubs.update');
    Route::delete('clubs/{association}', [ComitesController::class, 'destroyClub'])->name('clubs.destroy');
    Route::post('clubs/{association}/asignar-presidenta', [ComitesController::class, 'asignarPresidenta'])->name('clubs.asignar-presidenta');

    // Reconocimientos (Resoluciones)
    Route::get('reconocimientos', [ComitesController::class, 'reconocimientos'])->name('reconocimientos');
    Route::get('reconocimientos/options', [ComitesController::class, 'reconocimientosOptions'])->name('reconocimientos.options');
    Route::post('reconocimientos', [ComitesController::class, 'storeReconocimiento'])->name('reconocimientos.store');
    Route::put('reconocimientos/{resolution}', [ComitesController::class, 'updateReconocimiento'])->name('reconocimientos.update');
    Route::delete('reconocimientos/{resolution}', [ComitesController::class, 'destroyReconocimiento'])->name('reconocimientos.destroy');

    // Portal municipal (proxy de resoluciones externas)
    Route::get('reconocimientos/{resolution}/buscar-externa', [ComitesController::class, 'buscarResolucionExterna'])->name('reconocimientos.externa.buscar');
    Route::get('reconocimientos/{resolution}/preview-externa', [ComitesController::class, 'previewResolucionExterna'])->name('reconocimientos.externa.preview');
    Route::get('reconocimientos/{resolution}/descargar-externa', [ComitesController::class, 'descargarResolucionExterna'])->name('reconocimientos.externa.descargar');
});

// ==================== MÓDULO: PRODUCTOS Y PECOSAS ====================
Route::prefix('dashboard/productos-pecosas')->name('api.productos-pecosas.')->group(function () {
    // Productos (slug de módulo: productos)
    Route::middleware('module:productos')->group(function () {
        Route::get('products', [ProductosPecosasController::class, 'products'])->name('products');
        Route::get('products/options', [ProductosPecosasController::class, 'productsOptions'])->name('products.options');
        Route::get('products/detail-products', [ProductosPecosasController::class, 'detailProducts'])->name('products.detail-products');
        Route::post('products', [ProductosPecosasController::class, 'storeProduct'])->name('products.store');
        Route::put('products/{product}', [ProductosPecosasController::class, 'updateProduct'])->name('products.update');
        Route::delete('products/{product}', [ProductosPecosasController::class, 'destroyProduct'])->name('products.destroy');
    });

    // Pecosas (slug de módulo: pecosas)
    Route::middleware('module:pecosas')->group(function () {
        Route::get('pecosas', [ProductosPecosasController::class, 'pecosas'])->name('pecosas');
        Route::get('pecosas/options', [ProductosPecosasController::class, 'pecosasOptions'])->name('pecosas.options');
        Route::post('pecosas', [ProductosPecosasController::class, 'storePecosa'])->name('pecosas.store');
        Route::put('pecosas/{pecosa}', [ProductosPecosasController::class, 'updatePecosa'])->name('pecosas.update');
        Route::delete('pecosas/{pecosa}', [ProductosPecosasController::class, 'destroyPecosa'])->name('pecosas.destroy');
    });
});

// ==================== MÓDULO: MOVIMIENTOS ====================
Route::prefix('dashboard/movimientos')->middleware('module:movimientos')->name('api.movimientos.')->group(function () {
    Route::get('transactions', [MovimientosController::class, 'index'])->name('transactions');
    Route::get('transactions/options', [MovimientosController::class, 'options'])->name('transactions.options');
    Route::post('transactions', [MovimientosController::class, 'store'])->name('transactions.store');
    Route::put('transactions/{transaction}', [MovimientosController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [MovimientosController::class, 'destroy'])->name('transactions.destroy');

    // Repartición mensual (reporte de raciones por comité + enlace al PDF)
    Route::get('reparticion', [MovimientosController::class, 'reparticion'])->name('reparticion');
});
