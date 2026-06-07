<?php

use App\Http\Controllers\ProductosPecosasController;
use Illuminate\Support\Facades\Route;

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
