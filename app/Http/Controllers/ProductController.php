<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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
        $states = State::select(['id', 'title', 'abbreviation'])->get();
        $uoms = Uom::select(['id', 'title'])->get();

        return view('productos-pecosas.productos.index', compact('products', 'states', 'uoms'));
    }

    public function create()
    {
        $states = State::all();
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
        $states = State::all();
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