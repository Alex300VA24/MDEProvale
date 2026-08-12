<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\Pecosa;
use App\Models\Product;
use App\Models\Racion;
use App\Models\State;

class InicioController extends Controller
{
    /**
     * KPIs agregados del panel de inicio. Disponible para cualquier usuario
     * autenticado (la sección 'Inicio' no está atada a ningún módulo, ver
     * NAV_ITEMS en Dashboard.jsx): son solo conteos, sin datos personales.
     */
    public function kpis()
    {
        $activeStateId = State::where('abbreviation', 'A')->value('id');
        $today = now()->toDateString();

        $sociosActivos = Partner::where('state_id', $activeStateId)->count();

        $beneficiariosActivos = Beneficiarie::whereHas('histories', function ($q) use ($today) {
            $q->where(function ($qq) use ($today) {
                $qq->whereNull('date_begin')->orWhere('date_begin', '<=', $today);
            })->where(function ($qq) use ($today) {
                $qq->whereNull('date_end')->orWhere('date_end', '>=', $today);
            });
        })->count();

        $comitesActivos = Association::where('state_id', $activeStateId)->count();

        $productosStockCritico = Product::with(['detailProducts' => function ($q) {
            $q->withSum('stocks as used_quantity', 'quantity');
        }])->get()->filter(fn ($p) => $p->stock <= 10)->count();

        $pecosasMesActual = Pecosa::whereMonth('delivery_date', now()->month)
            ->whereYear('delivery_date', now()->year)
            ->count();

        $racionActiva = Racion::where('year', now()->year)->where('active', true)->first();

        return response()->json([
            'socios_activos' => $sociosActivos,
            'beneficiarios_activos' => $beneficiariosActivos,
            'comites_activos' => $comitesActivos,
            'productos_stock_critico' => $productosStockCritico,
            'pecosas_mes_actual' => $pecosasMesActual,
            'racion_activa' => $racionActiva ? [
                'year' => (int) $racionActiva->year,
                'racion_leche_militros' => (float) $racionActiva->racion_leche_militros,
                'racion_hojuelas_gramos' => (float) $racionActiva->racion_hojuelas_gramos,
            ] : null,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
