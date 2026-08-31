<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\Pecosa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
{
    /**
     * Año mínimo seleccionable en las gráficas del panel de inicio.
     */
    private const MIN_YEAR = 2019;

    /**
     * Datos del panel de inicio (tarjetas KPI + gráficas). Disponible para
     * cualquier usuario autenticado (la sección 'Inicio' no está atada a
     * ningún módulo, ver NAV_ITEMS en Dashboard.jsx): son solo conteos y
     * agregados, sin datos personales.
     *
     * Cada gráfica se filtra de forma independiente (cambiar el año de una no
     * afecta a las demás). Parámetros de query opcionales:
     * - anio_pecosas: año de "PECOSAs por mes" (por defecto, el año actual).
     * - anio_productos: año de "Productos distribuidos" (por defecto, el actual).
     * - socios_anio / socios_mes: periodo de la comparativa "Socios vs
     *   Beneficiarios". socios_mes = 0 significa "año completo".
     */
    public function panel(Request $request)
    {
        $currentYear = now()->year;

        $yearPecosas = $this->clampYear((int) $request->query('anio_pecosas', $currentYear), $currentYear);
        $yearProductos = $this->clampYear((int) $request->query('anio_productos', $currentYear), $currentYear);

        $sociosYear = $this->clampYear((int) $request->query('socios_anio', $currentYear), $currentYear);
        $sociosMonth = (int) $request->query('socios_mes', 0);
        if ($sociosMonth < 1 || $sociosMonth > 12) {
            $sociosMonth = 0;
        }

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

        $pecosasYearEnd = $yearPecosas . '-12-31';

        // PECOSAs por mes (año seleccionado).
        // Nota: se usa el query builder (no el modelo Eloquent Pecosa) porque
        // Pecosa::getMonthAttribute() es un accessor que pisa el alias "month"
        // seleccionado aquí y lo devuelve siempre en null al hidratar el modelo.
        // Rango ampliado (dic. del año anterior a dic. de este año) porque una
        // entrega en la última semana de un mes se contabiliza en el mes
        // siguiente (representa la PECOSA de ese mes siguiente), pudiendo
        // desplazar diciembre hacia enero del año actual.
        $pecosas = DB::table('pecosas')
            ->select('delivery_date')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [($yearPecosas - 1) . '-12-01', $pecosasYearEnd])
            ->get();

        $pecosaData = array_fill(0, 12, 0);
        foreach ($pecosas as $item) {
            $effective = Pecosa::effectiveDeliveryDate($item->delivery_date);

            if ((int) $effective->year === $yearPecosas) {
                $pecosaData[$effective->month - 1]++;
            }
        }
        $totalPecosasAnio = array_sum($pecosaData);

        // Productos distribuidos por mes (Leche / Hojuelas).
        // Se toma la salida real registrada en product_stocks y se ubica en el
        // mes del movimiento de ingreso (detail_products.start_date .. end_date,
        // siempre un mes calendario). No se usa la fecha de la PECOSA ni el
        // desplazamiento de fin de mes: la base de origen a veces parte una
        // ración en dos PECOSAs a ambos lados del corte de fin de mes, o carga
        // la ración de un mes en el lote del mes siguiente, lo que dejaba el
        // alimento en cero ese mes (p. ej. leche enero 2026).
        $productosPorMes = DB::table('product_stocks')
            ->join('detail_products', 'product_stocks.detail_product_id', '=', 'detail_products.id')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->whereYear('detail_products.start_date', $yearProductos)
            ->groupBy('mes', 'products.title')
            ->get([
                DB::raw('MONTH(detail_products.start_date) as mes'),
                'products.title as product',
                DB::raw('SUM(product_stocks.quantity) as total'),
            ]);

        $lecheData = array_fill(0, 12, 0);
        $hojuelasData = array_fill(0, 12, 0);
        foreach ($productosPorMes as $item) {
            $mes = (int) $item->mes - 1;
            if ($mes < 0 || $mes > 11) {
                continue;
            }
            if (stripos($item->product, 'leche') !== false) {
                $lecheData[$mes] += (int) $item->total;
            } elseif (stripos($item->product, 'hojuela') !== false) {
                $hojuelasData[$mes] += (int) $item->total;
            }
        }

        // Socios y beneficiarios vigentes en el periodo seleccionado.
        // No se usan created_at/updated_at: la vigencia se calcula por
        // solapamiento de fechas de alta/baja (date_begin / date_end) con el
        // rango del periodo. Para beneficiarios, esas fechas viven en
        // beneficiary_histories (la tabla beneficiaries no tiene fecha propia).
        [$periodoInicio, $periodoFin] = $this->sociosPeriodo($sociosYear, $sociosMonth);

        $sociosVigentes = DB::table('partners')
            ->whereDate('date_begin', '<=', $periodoFin)
            ->where(function ($query) use ($periodoInicio) {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $periodoInicio);
            })
            ->count();

        $beneficiariosVigentes = DB::table('beneficiary_histories')
            ->whereDate('date_begin', '<=', $periodoFin)
            ->where(function ($query) use ($periodoInicio) {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $periodoInicio);
            })
            ->distinct()
            ->count('beneficiary_id');

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
                'anio' => $yearPecosas,
            ],
            'productos_distribuidos' => [
                'leche' => $lecheData,
                'hojuelas' => $hojuelasData,
                'anio' => $yearProductos,
            ],
            'socios_vs_beneficiarios' => [
                'socios' => $sociosVigentes,
                'beneficiarios' => $beneficiariosVigentes,
                'anio' => $sociosYear,
                'mes' => $sociosMonth,
            ],
            'anio_min' => self::MIN_YEAR,
            'anio_actual' => $currentYear,
            'top_comites' => $topComites,
        ]);
    }

    /**
     * Acota un año al rango [MIN_YEAR, año actual].
     */
    private function clampYear(int $year, int $currentYear): int
    {
        if ($year < self::MIN_YEAR || $year > $currentYear) {
            return $currentYear;
        }

        return $year;
    }

    /**
     * Rango de fechas [inicio, fin] del periodo de la comparativa
     * "Socios vs Beneficiarios". mes = 0 abarca el año completo.
     *
     * @return array{0: string, 1: string}
     */
    private function sociosPeriodo(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            return ["$year-01-01", "$year-12-31"];
        }

        $inicio = Carbon::create($year, $month, 1)->startOfMonth();

        return [$inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString()];
    }
}
