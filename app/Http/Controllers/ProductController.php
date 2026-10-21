<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(Request $request)
    {
        $data = $this->productService->indexData(
            $request->only(['search', 'state_id', 'uom_id']),
            [
                'product_id' => $request->input('product_id'),
                'periodo' => $request->input('periodo'),
                'search' => $request->input('search_detalle'),
            ]
        );

        return view('productos-pecosas.productos.index', $data);
    }

    public function create()
    {
        return view('productos-pecosas.productos.create', $this->productService->formCatalogs());
    }

    public function store(StoreProductRequest $request)
    {
        $this->productService->create($request->validated());

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente');
    }

    public function storeAjax(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->create($request->validated());

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
        $product = $this->productService->show($product);

        return view('productos-pecosas.productos.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('productos-pecosas.productos.edit', ['product' => $product] + $this->productService->formCatalogs());
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        try {
            $this->productService->delete($product);
        } catch (\DomainException $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }

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
