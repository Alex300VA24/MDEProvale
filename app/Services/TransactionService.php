<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private TransactionRepository $transactionRepo;
    private StockService $stockService;

    public function __construct(TransactionRepository $transactionRepo, StockService $stockService)
    {
        $this->transactionRepo = $transactionRepo;
        $this->stockService = $stockService;
    }

    public function searchTransactions(array $filters, int $perPage = 15)
    {
        return $this->transactionRepo->searchTransactions($filters, $perPage);
    }

    public function registerIngreso(array $data): Transaction
    {
        $typeIngreso = TypeTransaction::whereRaw('LOWER(title) = ?', ['ingreso'])->firstOrFail();

        return DB::transaction(function () use ($data, $typeIngreso) {
            $detailProduct = DetailProduct::create([
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addYear()->toDateString(),
            ]);

            $detailProduct->load('product.uom');

            return Transaction::create([
                'detail_product_id' => $detailProduct->id,
                'type_transaction_id' => $typeIngreso->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['quantity'] * $data['unit_price'],
                'document_number' => $data['document_number'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'product_name' => $detailProduct->product ? $detailProduct->product->title : null,
                'uom_title' => $detailProduct->product ? ($detailProduct->product->uom ? $detailProduct->product->uom->title : null) : null,
            ]);
        });
    }

    public function registerSalida(array $data): Transaction
    {
        $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->firstOrFail();
        $detailProduct = DetailProduct::with('product.uom')->findOrFail($data['detail_product_id']);

        return DB::transaction(function () use ($data, $typeSalida, $detailProduct) {
            $this->stockService->deductByProduct($detailProduct->product_id, $data['quantity'], $data['pecosa_id'] ?? null);

            return Transaction::create([
                'detail_product_id' => $data['detail_product_id'],
                'type_transaction_id' => $typeSalida->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['quantity'] * $data['unit_price'],
                'document_number' => $data['document_number'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'product_name' => $detailProduct->product ? $detailProduct->product->title : null,
                'uom_title' => $detailProduct->product ? ($detailProduct->product->uom ? $detailProduct->product->uom->title : null) : null,
            ]);
        });
    }
}