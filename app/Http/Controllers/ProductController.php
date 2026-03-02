<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\State;
use App\Models\Uom;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with(['state', 'uom']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('abbreviation', 'like', "%{$search}%");
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $products = $query->orderBy('id', 'desc')->paginate(10);
        $states = State::all();
        return view('productos.index', compact('products', 'states'));
    }

    public function create()
    {
        $states = State::all();
        $uoms = Uom::all();
        return view('productos.create', compact('states', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'state_id' => 'required|exists:states,id',
            'uom_id' => 'required|exists:uoms,id',
        ]);
        Product::create($validated);
        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente');
    }

    public function show(Product $product)
    {
        return view('productos.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $states = State::all();
        $uoms = Uom::all();
        return view('productos.edit', compact('product', 'states', 'uoms'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'state_id' => 'required|exists:states,id',
            'uom_id' => 'required|exists:uoms,id',
        ]);
        $product->update($validated);
        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente');
    }

    public function reportes()
    {
        return view('productos.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Product::with(['state', 'uom']);

        switch ($tipo) {
            case 'general':
                $products = $query->get();
                $titulo = 'Inventario General de Productos';
                break;
            case 'stock-bajo':
                $stockMinimo = $request->get('stock_minimo', 10);
                $products = $query->where('stock', '<=', $stockMinimo)->get();
                $titulo = 'Productos con Stock Bajo (≤ ' . $stockMinimo . ')';
                break;
            case 'valorizacion':
                $products = $query->get();
                $titulo = 'Valorización de Inventario';
                break;
            case 'movimientos':
                $products = $query->with('transactions')->get();
                $titulo = 'Productos con Movimientos';
                break;
            case 'top':
                $products = $query->withCount('transactions')->orderBy('transactions_count', 'desc')->limit(10)->get();
                $titulo = 'Top 10 Productos Más Utilizados';
                break;
            default:
                $products = $query->get();
                $titulo = 'Reporte de Productos';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.productos', compact('products', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-productos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
