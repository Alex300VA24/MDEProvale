<?php

namespace App\Http\Controllers;

use App\Models\DetailProduct;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['product_id', 'periodo']);
        $detailProducts = $this->productRepo->getDetailProductsWithStock(
            $filters['product_id'] ?? null,
            $filters['periodo'] ?? null
        );

        $products = Product::select(['id', 'title', 'abbreviation'])->get();

        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id'])
            ->withSum('stocks as used_quantity', 'quantity');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', $today);
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', $today);
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->stock_status;
            if ($stockStatus === 'disponible') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) > 0');
            } elseif ($stockStatus === 'agotado') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) <= 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "{$search}%")
                  ->orWhere('abbreviation', 'like', "{$search}%");
            });
        }

        $detailProducts = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('productos-pecosas.kardex.index', compact('detailProducts', 'products'));
    }
}