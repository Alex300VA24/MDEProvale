<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    return view('auth.login');
});

// Ruta pública para refrescar CSRF token
Route::get('/refresh-csrf', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->middleware('web');

// Rutas públicas para recuperación de contraseña (SIN autenticación)
Route::post('password-reset-request', [App\Http\Controllers\SistemaController::class, 'requestPasswordReset'])->name('password-reset-request');

// Rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    // Dashboard SPA (Inertia + React). El resto de la navegación es 100% client-side.
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    require __DIR__ . '/search.php';
    require __DIR__ . '/socios-beneficiarios.php';
    require __DIR__ . '/club-reconocimientos.php';
    require __DIR__ . '/productos-pecosas.php';
    require __DIR__ . '/movimientos.php';
    require __DIR__ . '/sistema.php';
    require __DIR__ . '/responsables-raciones.php';
    require __DIR__ . '/reportes.php';
});

require __DIR__ . '/auth.php';
