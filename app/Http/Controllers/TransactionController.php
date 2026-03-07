<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['product', 'typeTransaction']);

        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type_transaction_id')) {
            $query->where('type_transaction_id', $request->type_transaction_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);
        $types = \App\Models\TypeTransaction::all();

        return view('movimientos.index', compact('transactions', 'types'));
    }

    public function create()
    {
        $products = Product::all();
        $types = \App\Models\TypeTransaction::all();
        $states = State::all();
        return view('movimientos.create', compact('products', 'types', 'states'));
    }

    public function store(Request $request)
    {
        Transaction::create($request->all());
        return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['product', 'typeTransaction']);
        return view('movimientos.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $products = Product::all();
        $types = \App\Models\TypeTransaction::all();
        $states = State::all();
        return view('movimientos.edit', compact('transaction', 'products', 'types', 'states'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $transaction->update($request->all());
        return redirect()->route('movimientos.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
    }

    /**
     * Vista de reportes de movimientos
     */
    public function reportes()
    {
        return view('movimientos.reportes');
    }

    /**
     * Generar reporte de movimientos por tipo
     */
    public function generarReporte(Request $request, $tipo)
    {
        $query = Transaction::with(['product', 'typeTransaction']);
        $titulo = 'Reporte de Movimientos';

        switch ($tipo) {
            case 'ingresos':
                $query->whereHas('typeTransaction', function ($q) {
                    $q->where('title', 'Ingreso');
                });
                $titulo = 'Reporte de Ingresos';
                break;
            case 'salidas':
                $query->whereHas('typeTransaction', function ($q) {
                    $q->where('title', 'like', '%Salida%');
                });
                $titulo = 'Reporte de Salidas';
                break;
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();
        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('movimientos.reportes.pdf', compact('transactions', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-movimientos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Generar Comprobante de Salida para un movimiento
     */
    public function generarComprobanteSalida(Request $request)
    {
        $transaction_id = $request->get('transaction_id');

        if ($transaction_id) {
            $transaction = Transaction::with(['product.uom', 'typeTransaction'])->findOrFail($transaction_id);

            $articulos = [
                [
                    'numero' => '01',
                    'cantidad_solicitado' => number_format($transaction->quantity, 2),
                    'descripcion' => strtoupper($transaction->product->title ?? ''),
                    'cantidad_despachado' => number_format($transaction->quantity, 2),
                    'unidad' => strtoupper($transaction->product->uom->title ?? 'KILOS'),
                    'unitario' => number_format($transaction->unit_price, 2),
                    'total' => number_format($transaction->total_price, 2),
                ],
            ];

            $data = [
                'zona' => $request->get('zona', ''),
                'comite' => $request->get('comite', ''),
                'num_mes' => date('m'),
                'racion' => '',
                'numero_orden' => str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                'solicitante_nombre' => $request->get('solicitante', ''),
                'domicilio' => $request->get('domicilio', ''),
                'fecha' => date('d \d\e F \d\e\l Y'),
                'articulos' => $articulos,
                'total_general' => '****' . number_format($transaction->total_price, 2),
            ];
        } else {
            $data = [
                'zona' => '',
                'comite' => '',
                'num_mes' => '',
                'racion' => '',
                'numero_orden' => '',
                'solicitante_nombre' => '',
                'domicilio' => '',
                'fecha' => date('d \d\e F \d\e\l Y'),
                'articulos' => [],
                'total_general' => '*****0.00',
            ];
        }

        $pdf = \PDF::loadView('comprobante_salida', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('comprobante-salida-' . date('Y-m-d') . '.pdf');
    }
}
