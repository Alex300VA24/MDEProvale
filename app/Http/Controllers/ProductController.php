<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Product;
use App\Models\State;
use App\Models\Uom;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductRepository $productRepo;
    private ProductService $productService;

    public function __construct(ProductRepository $productRepo, ProductService $productService)
    {
        $this->productRepo = $productRepo;
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'state_id', 'uom_id']);
        $products = $this->productRepo->searchWithFilters($filters);
        $states = State::temporal()->get(['id', 'title', 'abbreviation']);
        $uoms = Uom::select(['id', 'title'])->get();

        $detailQuery = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id'])
            ->withSum('stocks as used_quantity', 'quantity');

        if ($request->filled('product_id')) {
            $detailQuery->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $detailQuery->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $detailQuery->where('end_date', '<', $today);
            }
        }

        if ($request->filled('search_detalle')) {
            $searchDetalle = $request->search_detalle;
            $detailQuery->whereHas('product', function ($q) use ($searchDetalle) {
                $q->where('title', 'like', "{$searchDetalle}%")
                  ->orWhere('abbreviation', 'like', "{$searchDetalle}%");
            });
        }

        $detailProducts = $detailQuery->orderBy('created_at', 'desc')->paginate(15);

        return view('productos-pecosas.productos.index', compact('products', 'states', 'uoms', 'detailProducts'));
    }

    public function create()
    {
        $states = State::temporal()->get();
        $uoms = Uom::all();
        return view('productos-pecosas.productos.create', compact('states', 'uoms'));
    }

    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());
        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente');
    }

    public function storeAjax(StoreProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());
            return response()->json([
                'success' => true,
                'product' => $product,
                'message' => 'Producto registrado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar producto: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show(Product $product)
    {
        $product->load(['detailProducts' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);
        return view('productos-pecosas.productos.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $states = State::temporal()->get();
        $uoms = Uom::all();
        return view('productos-pecosas.productos.edit', compact('product', 'states', 'uoms'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        $referencedByPecosa = DetailPecosa::whereHas('detailProduct', function ($q) use ($product) {
            $q->where('product_id', $product->id);
        })->exists();

        $hasStock = DetailProduct::where('product_id', $product->id)
            ->whereHas('stocks')
            ->exists();

        if ($referencedByPecosa || $hasStock) {
            return redirect()->route('products.index')
                ->with('error', 'No se puede eliminar: el producto tiene detalles/stock asociado');
        }

        DetailProduct::where('product_id', $product->id)->delete();
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente');
    }

    public function reportes()
    {
        return view('productos-pecosas.productos.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $filters = $request->only(['stock_minimo']);
        $data = $this->productService->generateReport($tipo, $filters);

        return view('productos-pecosas.productos.reportes.pdf', array_merge($data, ['tipo' => $tipo]));
    }
}
