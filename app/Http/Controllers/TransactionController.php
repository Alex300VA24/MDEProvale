<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\TypeTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['product', 'typeTransaction']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->has('type_transaction_id') && $request->type_transaction_id != '') {
            $query->where('type_transaction_id', $request->type_transaction_id);
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(10);
        $types = TypeTransaction::all();
        return view('movimientos.index', compact('transactions', 'types'));
    }

    public function create()
    {
        $products = Product::all();
        $types = TypeTransaction::all();
        return view('movimientos.create', compact('products', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type_transaction_id' => 'required|exists:type_transactions,id',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);
        $validated['total_price'] = $validated['quantity'] * $validated['unit_price'];
        Transaction::create($validated);
        return redirect()->route('movimientos.index')->with('success', 'Movimiento creado exitosamente');
    }

    public function show(Transaction $transaction)
    {
        return view('movimientos.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $products = Product::all();
        $types = TypeTransaction::all();
        return view('movimientos.edit', compact('transaction', 'products', 'types'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type_transaction_id' => 'required|exists:type_transactions,id',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);
        $validated['total_price'] = $validated['quantity'] * $validated['unit_price'];
        $transaction->update($validated);
        return redirect()->route('movimientos.index')->with('success', 'Movimiento actualizado exitosamente');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado exitosamente');
    }

    public function reportes()
    {
        return view('movimientos.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Transaction::with(['product', 'typeTransaction']);

        switch ($tipo) {
            case 'general':
                $transactions = $query->get();
                $titulo = 'Todos los Movimientos';
                break;
            case 'ingresos':
                $transactions = $query->where('type_transaction_id', 1);
                if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                    $transactions = $transactions->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin]);
                }
                $transactions = $transactions->get();
                $titulo = 'Movimientos de Ingreso';
                break;
            case 'salidas':
                $transactions = $query->where('type_transaction_id', 2);
                if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                    $transactions = $transactions->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin]);
                }
                $transactions = $transactions->get();
                $titulo = 'Movimientos de Salida';
                break;
            case 'producto':
                $productId = $request->get('product_id');
                $transactions = $query->where('product_id', $productId)->get();
                $product = Product::find($productId);
                $titulo = 'Movimientos del Producto: ' . ($product->title ?? 'N/A');
                break;
            case 'estadistico':
                $transactions = $query->get();
                $titulo = 'Reporte Estadístico de Movimientos';
                break;
            case 'valorizacion':
                $transactions = $query->get();
                $titulo = 'Valorización de Movimientos';
                break;
            default:
                $transactions = $query->get();
                $titulo = 'Reporte de Movimientos';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.movimientos', compact('transactions', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-movimientos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
