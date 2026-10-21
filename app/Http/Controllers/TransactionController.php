<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type_transaction_id', 'fecha_inicio', 'fecha_fin']);

        return view('movimientos.index', $this->transactionService->indexData($filters));
    }

    public function store(StoreTransactionRequest $request)
    {
        try {
            $isIngreso = $this->transactionService->isIngresoType((int) $request->input('type_transaction_id'));

            $isIngreso
                ? $this->transactionService->registerIngreso($request->validated())
                : $this->transactionService->registerSalida($request->validated());

            $message = $isIngreso ? 'Ingreso registrado correctamente.' : 'Salida registrada correctamente.';
            return redirect()->route('movimientos.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('movimientos.index')->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'document_number' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $this->transactionService->updateTransaction($transaction, $validated);
            return redirect()->route('movimientos.index')->with('success', 'Movimiento actualizado correctamente.');
        } catch (\Throwable $e) {
            return redirect()->route('movimientos.index')->with('error', $e->getMessage());
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactionService->deleteTransaction($transaction);
            return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
        } catch (\Throwable $e) {
            return redirect()->route('movimientos.index')->with('error', $e->getMessage());
        }
    }
}
