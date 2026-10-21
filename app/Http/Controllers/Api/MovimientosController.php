<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
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
        $filters = $request->only(['search', 'type_transaction_id', 'year', 'month', 'fecha_inicio', 'fecha_fin']);
        $transactions = $this->transactionService->searchTransactions($filters, (int) $request->input('per_page', 15));

        return TransactionResource::collection($transactions);
    }

    public function options()
    {
        return response()->json($this->transactionService->options(), 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function store(StoreTransactionRequest $request)
    {
        try {
            $transaction = $this->transactionService->register($request->validated());

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

        if ($this->reparticionService->isBeforeFirstRecordedPeriod($year, $month)) {
            return response()->json([
                'code' => 'PERIOD_RECORD_NOT_FOUND',
                'message' => $this->reparticionService->periodRecordNotFoundMessage($year, $month),
            ], 404);
        }

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        if (!$racion) {
            return response()->json([
                'message' => "No hay ración configurada para el período {$month}/{$year}. Configure las raciones en Responsables y Raciones.",
            ], 404);
        }

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            return response()->json([
                'code' => 'INGRESO_REQUIRED',
                'message' => $this->reparticionService->ingresoRequiredMessage($year, $month),
            ], 422);
        }

        $report = $this->reparticionService->buildReport($racion, $year, $month);
        $common = ['year' => $year, 'month' => $month];
        $report['exports'] = [
            'reparto_pdf' => route('movimientos.distribucion.export', [...$common, 'document' => 'reparto', 'format' => 'pdf']),
            'reparto_excel' => route('movimientos.distribucion.export', [...$common, 'document' => 'reparto', 'format' => 'xlsx']),
            'cargo_pdf' => route('movimientos.distribucion.export', [...$common, 'document' => 'cargo', 'format' => 'pdf']),
            'cargo_excel' => route('movimientos.distribucion.export', [...$common, 'document' => 'cargo', 'format' => 'xlsx']),
            'fiscalizacion_pdf' => route('movimientos.distribucion.export', [...$common, 'document' => 'fiscalizacion', 'format' => 'pdf']),
            'fiscalizacion_excel' => route('movimientos.distribucion.export', [...$common, 'document' => 'fiscalizacion', 'format' => 'xlsx']),
            'acta_pdf' => route('movimientos.distribucion.export', [...$common, 'document' => 'acta', 'format' => 'pdf']),
        ];
        $report['pdf_url'] = $report['exports']['reparto_pdf'];
        $report['pdf_firma_url'] = $report['exports']['acta_pdf'];

        return response()->json($report, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function saveReparticion(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'service_days' => 'required|integer|min:28|max:31',
            'milk_grams_per_beneficiary' => 'required|numeric|gt:0|max:10000',
            'oat_grams_per_beneficiary' => 'required|numeric|gt:0|max:10000',
            'milk_can_grams' => 'required|numeric|gt:0|max:10000',
            'oat_bag_grams' => 'required|numeric|gt:0|max:10000',
            'milk_cans_per_box' => 'required|integer|min:1|max:1000',
            'oat_kg_per_sack' => 'required|integer|min:1|max:1000',
            'assignments' => 'present|array',
            'assignments.*.association_id' => 'required|integer|exists:associations,id',
            'assignments.*.route_number' => 'required|integer|min:1|max:999',
            'assignments.*.beneficiary_adjustment' => 'required|integer|min:-100000|max:100000',
            'assignments.*.observation' => 'nullable|string|max:250',
        ]);

        if ($this->reparticionService->isBeforeFirstRecordedPeriod($validated['year'], $validated['month'])) {
            return response()->json([
                'code' => 'PERIOD_RECORD_NOT_FOUND',
                'message' => $this->reparticionService->periodRecordNotFoundMessage($validated['year'], $validated['month']),
            ], 404);
        }

        $calendarDays = $this->reparticionService->calendarDays($validated['year'], $validated['month']);
        if ($validated['service_days'] > $calendarDays) {
            return response()->json([
                'message' => "El período seleccionado admite como máximo {$calendarDays} días de atención.",
                'errors' => ['service_days' => ["Use un valor entre 28 y {$calendarDays}."]],
            ], 422);
        }

        $racion = $this->reparticionService->getActiveRacion($validated['year'], $validated['month']);
        if (! $racion) {
            return response()->json(['message' => 'No hay ración configurada para el período seleccionado.'], 422);
        }

        if (! $this->reparticionService->hasIngresoForPeriod($validated['year'], $validated['month'])) {
            return response()->json([
                'code' => 'INGRESO_REQUIRED',
                'message' => $this->reparticionService->ingresoRequiredMessage($validated['year'], $validated['month']),
            ], 422);
        }

        $this->reparticionService->saveConfiguration($validated);

        return $this->reparticion($request);
    }
}
