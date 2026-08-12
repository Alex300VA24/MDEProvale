<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Services\TransactionService;
use App\Services\PDFService;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    private TransactionService $transactionService;
    private TransactionRepository $transactionRepo;
    private PDFService $pdfService;

    public function __construct(TransactionService $transactionService, TransactionRepository $transactionRepo, PDFService $pdfService)
    {
        $this->transactionService = $transactionService;
        $this->transactionRepo = $transactionRepo;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type_transaction_id', 'fecha_inicio', 'fecha_fin']);
        $transactions = $this->transactionRepo->searchTransactions($filters);
        $types = TypeTransaction::all();
        $products = Product::select(['id', 'title', 'abbreviation'])->get();

        return view('movimientos.index', compact('transactions', 'types', 'products'));
    }

    public function store(StoreTransactionRequest $request)
    {
        try {
            $type = $request->input('type_transaction_id');
            $typeModel = TypeTransaction::find($type);
            $isIngreso = $typeModel && $typeModel->isIngreso();

            $transaction = $isIngreso
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
