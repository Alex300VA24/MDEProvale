<?php

use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\BeneficiarieController;
use Illuminate\Support\Facades\Route;

// ==================== MÓDULO: SOCIOS Y BENEFICIARIOS ====================
Route::prefix('socios-beneficiarios')->name('socios-beneficiarios.')->middleware('module:socios-beneficiarios')->group(function () {
    // Índice principal - muestra lista de socios con sus beneficiarios
    Route::get('/', [PartnerController::class, 'index'])->name('index');

    // Personas
    Route::get('personas', [PersonaController::class, 'index'])->name('personas.index');
    Route::post('personas', [PersonaController::class, 'store'])->name('personas.store');
    Route::put('personas/{person}', [PersonaController::class, 'update'])->name('personas.update');
    Route::delete('personas/{person}', [PersonaController::class, 'destroy'])->name('personas.destroy');

    // Socios
    Route::post('socios', [PartnerController::class, 'store'])->name('socios.store');
    Route::put('socios/{partner}', [PartnerController::class, 'update'])->name('socios.update');
    Route::delete('socios/{partner}', [PartnerController::class, 'destroy'])->name('socios.destroy');

    // Beneficiarios
    Route::get('beneficiarios', [BeneficiarieController::class, 'index'])->name('beneficiarios.index');
    Route::post('beneficiarios', [BeneficiarieController::class, 'store'])->name('beneficiarios.store');
    Route::put('beneficiarios/{beneficiarie}', [BeneficiarieController::class, 'update'])->name('beneficiarios.update');
    Route::delete('beneficiarios/{beneficiarie}', [BeneficiarieController::class, 'destroy'])->name('beneficiarios.destroy');
    Route::get('beneficiarios-imprimir', [BeneficiarieController::class, 'imprimirFicha'])->name('beneficiarios.imprimir');
    Route::get('beneficiarios-padron', [PartnerController::class, 'reportePadronBeneficiarios'])->name('beneficiarios.padron');
});
