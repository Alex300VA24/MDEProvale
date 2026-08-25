<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\DetailPecosa;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
{
    /**
     * Datos del panel de inicio (tarjetas KPI + gráficas). Disponible para
     * cualquier usuario autenticado (la sección 'Inicio' no está atada a
     * ningún módulo, ver NAV_ITEMS en Dashboard.jsx): son solo conteos y
     * agregados, sin datos personales.
     */
    public function panel()
    {
        $totalSocios = Partner::count();
        $totalBeneficiarios = Beneficiarie::count();
        $totalComites = Association::count();

        // Stock total: una sola query (cantidad ingresada - cantidad ya repartida
        // por cada lote), en vez de iterar cada detail_product en PHP.
        $stockTotal = (int) DB::table('detail_products')
            ->selectRaw('COALESCE(SUM(detail_products.quantity - COALESCE(used.total_used, 0)), 0) as stock')
            ->leftJoin(
                DB::raw('(SELECT detail_product_id, SUM(quantity) as total_used FROM product_stocks GROUP BY detail_product_id) as used'),
                'detail_products.id', '=', 'used.detail_product_id'
            )
            ->value('stock');

        // Stock visible en Inicio: saldo del ingreso más reciente de cada
        // alimento, descontando las salidas registradas para ese mismo lote.
        $salidasPorLote = DB::table('product_stocks')
            ->selectRaw('detail_product_id, SUM(quantity) as total_used')
            ->groupBy('detail_product_id');

        $ultimosIngresos = DB::table('detail_products')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoinSub($salidasPorLote, 'used', function ($join) {
                $join->on('detail_products.id', '=', 'used.detail_product_id');
            })
            ->where(function ($query) {
                $query->whereRaw('LOWER(products.title) LIKE ?', ['%hojuela%'])
                    ->orWhereRaw('LOWER(products.title) LIKE ?', ['%leche%']);
            })
            ->orderByDesc('detail_products.start_date')
            ->orderByDesc('detail_products.id')
            ->get([
                'products.title as product',
                'uoms.title as unit',
                'detail_products.start_date',
                DB::raw('(detail_products.quantity - COALESCE(used.total_used, 0)) as available_stock'),
            ]);

        $stockProductos = collect([
            ['key' => 'hojuelas', 'name' => 'Hojuelas', 'needle' => 'hojuela'],
            ['key' => 'leche', 'name' => 'Leche', 'needle' => 'leche'],
        ])->map(function ($definition) use ($ultimosIngresos) {
            $entry = $ultimosIngresos->first(
                fn ($item) => stripos((string) $item->product, $definition['needle']) !== false
            );

            return [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'stock' => $entry ? (int) $entry->available_stock : 0,
                'unit' => $entry ? (string) $entry->unit : '',
                'last_entry_date' => $entry ? $entry->start_date : null,
            ];
        })->values();

        $currentYear = now()->year;
        $yearStart = $currentYear . '-01-01';
        $yearEnd = $currentYear . '-12-31';

        // PECOSAs por mes (año actual).
        // Nota: se usa el query builder (no el modelo Eloquent Pecosa) porque
        // Pecosa::getMonthAttribute() es un accessor que pisa el alias "month"
        // seleccionado aquí y lo devuelve siempre en null al hidratar el modelo.
        $pecosasPorMes = DB::table('pecosas')
            ->selectRaw('MONTH(delivery_date) as month, COUNT(*) as total')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$yearStart, $yearEnd])
            ->groupByRaw('MONTH(delivery_date)')
            ->get();

        $pecosaData = array_fill(0, 12, 0);
        foreach ($pecosasPorMes as $item) {
            $month = (int) $item->month;
            if ($month >= 1 && $month <= 12) {
                $pecosaData[$month - 1] = (int) $item->total;
            }
        }
        $totalPecosasAnio = array_sum($pecosaData);

        // Productos distribuidos por mes (Leche / Hojuelas)
        $productosPorMes = DetailPecosa::selectRaw('MONTH(pecosas.delivery_date) as month, SUM(detail_pecosas.quantity) as total, products.title as product')
            ->join('pecosas', 'detail_pecosas.pecosa_id', '=', 'pecosas.id')
            ->join('detail_products', 'detail_pecosas.detail_product_id', '=', 'detail_products.id')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->whereBetween('pecosas.delivery_date', [$yearStart, $yearEnd])
            ->groupByRaw('MONTH(pecosas.delivery_date), products.title')
            ->get();

        $lecheData = array_fill(0, 12, 0);
        $hojuelasData = array_fill(0, 12, 0);
        foreach ($productosPorMes as $item) {
            $month = (int) $item->month;
            if ($month < 1 || $month > 12) {
                continue;
            }
            $mes = $month - 1;
            if (stripos($item->product, 'leche') !== false) {
                $lecheData[$mes] = (int) $item->total;
            } elseif (stripos($item->product, 'hojuela') !== false) {
                $hojuelasData[$mes] = (int) $item->total;
            }
        }

        // Top comités con más beneficiarios
        $topComites = Association::selectRaw('associations.name as club, COUNT(beneficiaries.id) as total')
            ->join('partners', 'partners.association_id', '=', 'associations.id')
            ->join('beneficiaries', 'beneficiaries.partner_id', '=', 'partners.id')
            ->groupBy('associations.id', 'associations.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($item) => ['nombre' => $item->club, 'total' => (int) $item->total])
            ->values();

        return response()->json([
            'stats' => [
                'total_socios' => $totalSocios,
                'total_beneficiarios' => $totalBeneficiarios,
                'total_comites' => $totalComites,
                'stock_total' => $stockTotal,
                'stock_productos' => $stockProductos,
            ],
            'pecosas_por_mes' => [
                'data' => $pecosaData,
                'total_anio' => $totalPecosasAnio,
                'anio' => $currentYear,
            ],
            'productos_distribuidos' => [
                'leche' => $lecheData,
                'hojuelas' => $hojuelasData,
                'anio' => $currentYear,
            ],
            'socios_vs_beneficiarios' => [
                'socios' => $totalSocios,
                'beneficiarios' => $totalBeneficiarios,
            ],
            'top_comites' => $topComites,
        ]);
    }
}
