<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InicioService;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __construct(private InicioService $inicio)
    {
    }

    /**
     * Datos del panel de inicio (tarjetas KPI + gráficas). Disponible para
     * cualquier usuario autenticado (la sección 'Inicio' no está atada a
     * ningún módulo): son solo conteos y agregados, sin datos personales.
     *
     * Parámetros de query opcionales: anio_pecosas, anio_productos,
     * periodo_anio y periodo_mes.
     */
    public function panel(Request $request)
    {
        return response()->json($this->inicio->panel($request->query()));
    }
}
