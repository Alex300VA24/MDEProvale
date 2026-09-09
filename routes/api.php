<?php

use App\Http\Controllers\Api\AssistantChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('platform.user')->group(function () {
        // Endpoints JSON del dashboard SPA (ver dashboard-api.php)
        require __DIR__ . '/dashboard-api.php';

        // Límite (5 consultas / 3 h por usuario) aplicado dentro del controlador
        // para poder informar el cupo restante en cada respuesta.
        Route::post('/asistente/chat', AssistantChatController::class);
    });
});
