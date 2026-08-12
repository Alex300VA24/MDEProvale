<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\DetailProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Services\ReparticionService;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class MovimientosController extends Controller
{
    private TransactionService $transactionService;
    private ReparticionService $reparticionService;

    public function __construct(TransactionService $transactionService, ReparticionService $reparticionService)
    {
        $this->transactionService = $transactionService;
        $this->reparticionService = $reparticionService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type_transaction_id', 'fecha_inicio', 'fecha_fin']);
        $transactions = $this->transactionService->searchTransactions($filters, (int) $request->input('per_page', 15));

        return TransactionResource::collection($transactions);
    }

    public function options()
    {
        $today = now()->toDateString();

        $detailProducts = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(fn ($dp) => [
                'id' => $dp->id,
                'product_id' => $dp->product_id,
                'product_title' => $dp->product->title ?? null,
                'product_abbreviation' => $dp->product->abbreviation ?? null,
                'uom_title' => $dp->product->uom->title ?? null,
                'unit_price' => (float) $dp->unit_price,
                'quantity' => (float) $dp->quantity,
                'used_quantity' => (float) ($dp->used_quantity ?? 0),
                'available_stock' => (float) ($dp->quantity - ($dp->used_quantity ?? 0)),
                'start_date' => $dp->start_date?->toDateString(),
                'end_date' => $dp->end_date?->toDateString(),
            ]);

        return response()->json([
            'types' => TypeTransaction::select(['id', 'title'])->get(),
            'products' => Product::select(['id', 'title', 'abbreviation'])->get(),
            'detail_products' => $detailProducts,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function store(StoreTransactionRequest $request)
    {
        try {
            $typeTransaction = TypeTransaction::find($request->input('type_transaction_id'));
            $isIngreso = $typeTransaction && $typeTransaction->isIngreso();

            $transaction = $isIngreso
                ? $this->transactionService->registerIngreso($request->validated())
                : $this->transactionService->registerSalida($request->validated());

            $resource = new TransactionResource($transaction->load(['typeTransaction:id,title', 'detailProduct.product:id,title,abbreviation']));

            return response()->json(['data' => $resource], 201, [], JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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
            $transaction = $this->transactionService->updateTransaction($transaction, $validated);

            $resource = new TransactionResource($transaction->load(['typeTransaction:id,title', 'detailProduct.product:id,title,abbreviation']));

            return response()->json(['data' => $resource], 200, [], JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactionService->deleteTransaction($transaction);

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reparticion(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));

        $racion = $this->reparticionService->getActiveRacion($year);
        if (!$racion) {
            return response()->json([
                'message' => "No hay ración configurada para el año {$year}. Configure las raciones en Mantenimiento.",
            ], 404);
        }

        $report = $this->reparticionService->buildReport($racion, $year, $month);
        $report['pdf_url'] = route('movimientos.reparticion', ['year' => $year, 'month' => $month]);

        return response()->json($report, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
