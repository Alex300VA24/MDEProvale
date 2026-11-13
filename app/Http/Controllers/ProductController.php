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
        return redirect()->route('dashboard');
    }

    public function create()
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreProductRequest $request)
    {
        $this->productService->register($request->validated());

        return redirect()->route('dashboard')->with('success', 'Producto creado exitosamente');
    }

    public function storeAjax(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->register($request->validated());

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
        return redirect()->route('dashboard');
    }

    public function edit(Product $product)
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('dashboard')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        try {
            $this->productService->delete($product);
        } catch (\DomainException $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Producto eliminado exitosamente');
    }

    public function reportes()
    {
        return redirect()->route('dashboard');
    }

    public function generarReporte($tipo, Request $request)
    {
        // El reporte HTML/PDF de productos migró al generador de reportes del dashboard.
        return redirect()->route('dashboard');
    }
}
