<?php

use App\Http\Controllers\SociosBeneficiariosController;
use Illuminate\Support\Facades\Route;

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
