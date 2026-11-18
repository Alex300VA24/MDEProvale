<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\PecosaController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// ==================== PRODUCTOS Y PROGRAMACIÓN / PECOSAS Y REPARTICIÓN ====================
// Cada submódulo tiene su propio permiso.
Route::prefix('productos-pecosas')->name('productos-pecosas.')->group(function () {
    // Productos (slug de submódulo: productos)
    Route::middleware('module:productos')->group(function () {
        // Índice principal
        Route::get('/', [ProductController::class, 'index'])->name('index');

        Route::get('productos', [ProductController::class, 'index'])->name('productos.index');
        Route::get('productos/crear', [ProductController::class, 'create'])->name('productos.create');
        Route::post('productos', [ProductController::class, 'store'])->name('productos.store');
        Route::post('productos/ajax', [ProductController::class, 'storeAjax'])->name('productos.store-ajax');
        Route::get('productos/{product}', [ProductController::class, 'show'])->name('productos.show');
        Route::get('productos/{product}/editar', [ProductController::class, 'edit'])->name('productos.edit');
        Route::put('productos/{product}', [ProductController::class, 'update'])->name('productos.update');
        Route::delete('productos/{product}', [ProductController::class, 'destroy'])->name('productos.destroy');
        Route::get('productos-reportes', [ProductController::class, 'reportes'])->name('productos.reportes');
        Route::get('productos-reporte/{tipo}', [ProductController::class, 'generarReporte'])->name('productos.generar-reporte');
    });

    // Registro de Ingreso (slug de submódulo: registro-ingreso): Kardex / Detalle Productos
    Route::get('productos-detalle', [KardexController::class, 'index'])
        ->middleware('module:registro-ingreso')
        ->name('productos-detalle');

    // Programación de Entrega (slug de submódulo: programacion-entrega)
    Route::get('pecosas-programacion-entrega', [ReportController::class, 'programacionEntrega'])
        ->middleware('module:programacion-entrega')
        ->name('pecosas.programacion-entrega');

    // Pecosas (slug de submódulo: pecosas)
    Route::middleware('module:pecosas')->group(function () {
        Route::get('pecosas', [PecosaController::class, 'index'])->name('pecosas.index');
        Route::get('pecosas/crear', [PecosaController::class, 'create'])->name('pecosas.create');
        Route::post('pecosas', [PecosaController::class, 'store'])->name('pecosas.store');
        Route::post('pecosas/verificar-vigencia', [PecosaController::class, 'verificarVigencia'])->name('pecosas.verificar-vigencia');
        Route::get('pecosas-pdf-mensual', [PecosaController::class, 'pdfMensual'])->name('pecosas.pdf-mensual');
        Route::get('pecosas/{pecosa}/comprobante', [PecosaController::class, 'generarComprobante'])->name('pecosas.comprobante');
        Route::get('pecosas/{pecosa}/pecosa-completa', [PecosaController::class, 'generarPecosaCompleta'])->name('pecosas.completa');
        Route::get('acta-supervision', [PecosaController::class, 'actaSupervision'])->name('pecosas.acta-supervision');
        Route::get('pecosas/{pecosa}', [PecosaController::class, 'show'])->name('pecosas.show');
        Route::get('pecosas/{pecosa}/editar', [PecosaController::class, 'edit'])->name('pecosas.edit');
        Route::put('pecosas/{pecosa}', [PecosaController::class, 'update'])->name('pecosas.update');
        Route::delete('pecosas/{pecosa}', [PecosaController::class, 'destroy'])->name('pecosas.destroy');
        Route::get('pecosas-reportes', [PecosaController::class, 'reportes'])->name('pecosas.reportes');
    });
});
