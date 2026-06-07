<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Beneficiarie;
use App\Models\Association;
use App\Models\Product;
use App\Models\Pecosa;
use App\Models\Transaction;
use App\Models\DetailProduct;
use App\Models\DetailPecosa;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Card stats (simple COUNT queries — 1 query each) ────────
        $totalPartners       = Partner::count();
        $activePartners      = Partner::where('state_id', 1)->count();
        $totalBeneficiaries  = Beneficiarie::count();
        $totalAssociations   = Association::count();
        $totalProducts       = Product::count();

        // ── Stock total — SINGLE query instead of N+1 loop ──────────
        // Before: DetailProduct::get()->sum(fn($dp) => $dp->quantity - ProductStock::where(...)->sum())
        // That was 1 + N queries. Now it's just 1 subquery.
        $totalStock = (int) DB::table('detail_products')
            ->selectRaw('COALESCE(SUM(detail_products.quantity - COALESCE(used.total_used, 0)), 0) as stock')
            ->leftJoin(
                DB::raw('(SELECT detail_product_id, SUM(quantity) as total_used FROM product_stocks GROUP BY detail_product_id) as used'),
                'detail_products.id', '=', 'used.detail_product_id'
            )
            ->value('stock');

        // ── Partners por club (top 10) ──────────────────────────────
        $partnersByClub = Partner::selectRaw('associations.name as club, COUNT(partners.id) as total')
            ->join('associations', 'partners.association_id', '=', 'associations.id')
            ->groupBy('associations.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── PECOSAs por mes (año actual) ────────────────────────────
        $currentYear = date('Y');
        $yearStart = $currentYear . '-01-01';
        $yearEnd = $currentYear . '-12-31';
        $pecosasByMonth = Pecosa::selectRaw('MONTH(delivery_date) as month, COUNT(*) as total')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$yearStart, $yearEnd])
            ->groupByRaw('MONTH(delivery_date)')
            ->get();

        $pecosaData = array_fill(0, 12, 0);
        foreach ($pecosasByMonth as $item) {
            $pecosaData[$item->month - 1] = $item->total;
        }

        // ── Ingresos por mes ────────────────────────────────────────
        $ingresosByMonth = Transaction::selectRaw('MONTH(transaction_date) as month, SUM(quantity) as total')
            ->where('type_transaction_id', 1)
            ->whereNotNull('transaction_date')
            ->whereBetween('transaction_date', [$yearStart, $yearEnd])
            ->groupByRaw('MONTH(transaction_date)')
            ->get();

        $ingresosData = array_fill(0, 12, 0);
        foreach ($ingresosByMonth as $item) {
            $ingresosData[$item->month - 1] = (int) $item->total;
        }

        // ── Productos distribuidos por mes (Leche/Hojuelas) ─────────
        $productosByMonth = DetailPecosa::selectRaw('MONTH(pecosas.delivery_date) as month, SUM(detail_pecosas.quantity) as total, products.title as product')
            ->join('pecosas', 'detail_pecosas.pecosa_id', '=', 'pecosas.id')
            ->join('detail_products', 'detail_pecosas.detail_product_id', '=', 'detail_products.id')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->whereBetween('pecosas.delivery_date', [$yearStart, $yearEnd])
            ->groupByRaw('MONTH(pecosas.delivery_date), products.title')
            ->get();

        $lecheData = array_fill(0, 12, 0);
        $hojuelasData = array_fill(0, 12, 0);
        foreach ($productosByMonth as $item) {
            $mes = $item->month - 1;
            if (stripos($item->product, 'leche') !== false) {
                $lecheData[$mes] = (int) $item->total;
            } elseif (stripos($item->product, 'hojuela') !== false) {
                $hojuelasData[$mes] = (int) $item->total;
            }
        }

        // ── Top clubs con más beneficiarios ─────────────────────────
        $clubsWithBeneficiarios = Association::selectRaw('associations.name as club, COUNT(beneficiaries.id) as total')
            ->join('partners', 'partners.association_id', '=', 'associations.id')
            ->join('beneficiaries', 'beneficiaries.partner_id', '=', 'partners.id')
            ->groupBy('associations.id', 'associations.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Totales para gráficas ───────────────────────────────────
        $sociosActivos          = $activePartners;
        $beneficiariosActivos   = $totalBeneficiaries;
        $totalPecosasAnio       = Pecosa::whereBetween('delivery_date', [$yearStart, $yearEnd])->count();

        return view('dashboard', compact(
            'totalPartners',
            'activePartners',
            'totalBeneficiaries',
            'totalAssociations',
            'totalProducts',
            'totalStock',
            'partnersByClub',
            'pecosaData',
            'ingresosData',
            'lecheData',
            'hojuelasData',
            'clubsWithBeneficiarios',
            'sociosActivos',
            'beneficiariosActivos',
            'totalPecosasAnio'
        ));
    }
}
