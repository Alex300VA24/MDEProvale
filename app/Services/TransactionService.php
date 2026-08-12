<?php

namespace App\Services;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Pecosa;
use App\Models\ProductStock;
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
                'product_name' => $detailProduct->product->title ?? null,
                'uom_title' => $detailProduct->product->uom->title ?? null,
            ]);
        });
    }

    public function registerSalida(array $data): Transaction
    {
        $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->firstOrFail();
        $detailProduct = DetailProduct::with('product.uom')->findOrFail($data['detail_product_id']);

        return DB::transaction(function () use ($data, $typeSalida, $detailProduct) {
            $transaction = Transaction::create([
                'detail_product_id' => $detailProduct->id,
                'type_transaction_id' => $typeSalida->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['quantity'] * $data['unit_price'],
                'document_number' => $data['document_number'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'product_name' => $detailProduct->product->title ?? null,
                'uom_title' => $detailProduct->product->uom->title ?? null,
            ]);

            // NOTA (limitación conocida): la transacción queda registrada contra el
            // lote seleccionado ($data['detail_product_id']), pero el descuento real
            // se hace FIFO por producto (deductByProduct) y puede tocar otros lotes.
            // No "arreglar" sin rediseñar el modelo stock/transacciones.
            $this->stockService->deductByProduct(
                $detailProduct->product_id,
                $data['quantity'],
                $data['pecosa_id'] ?? null,
                $transaction->id
            );

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction = $transaction->fresh();
            $transaction->load('typeTransaction');

            $quantity = (float) ($data['quantity'] ?? $transaction->quantity);
            $unitPrice = (float) ($data['unit_price'] ?? $transaction->unit_price);
            $updates = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
            ];
            foreach (['document_number', 'transaction_date'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field] ?? null;
                }
            }

            if ($transaction->typeTransaction && $transaction->typeTransaction->isSalida()) {
                if ($this->belongsToPecosa($transaction)) {
                    throw new \RuntimeException('No se puede editar: el movimiento pertenece a una Pecosa. Edite la Pecosa en el módulo de Pecosas.');
                }

                // Revierte el descuento anterior y vuelve a descontar con la nueva cantidad.
                $this->stockService->revertStockByTransaction($transaction->id);
                $transaction->update($updates);

                $detailProduct = DetailProduct::with('product.uom')->findOrFail($transaction->detail_product_id);
                $this->stockService->deductByProduct($detailProduct->product_id, $quantity, null, $transaction->id);

                return $transaction->fresh();
            }

            // Ingreso: actualiza el lote (DetailProduct) validando que no quede negativo.
            $detailProduct = DetailProduct::withSum('stocks as used_quantity', 'quantity')
                ->findOrFail($transaction->detail_product_id);
            $used = (float) ($detailProduct->used_quantity ?? 0);
            if ($quantity < $used) {
                throw new \RuntimeException("No se puede reducir la cantidad: el lote ya tiene {$used} unidades consumidas.");
            }

            $loteUpdates = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
            foreach (['start_date', 'end_date'] as $field) {
                if (!empty($data[$field])) {
                    $loteUpdates[$field] = $data[$field];
                }
            }
            $detailProduct->update($loteUpdates);
            $transaction->update($updates);

            return $transaction->fresh();
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = $transaction->fresh();
            $transaction->load('typeTransaction');

            if ($transaction->typeTransaction && $transaction->typeTransaction->isSalida()) {
                if ($this->belongsToPecosa($transaction)) {
                    throw new \RuntimeException('No se puede eliminar: el movimiento pertenece a una Pecosa. Elimine la Pecosa en el módulo de Pecosas.');
                }

                $this->stockService->revertStockByTransaction($transaction->id);
                $transaction->delete();
                return;
            }

            // Ingreso: elimina el lote creado, validando dependencias.
            $hasStock = ProductStock::where('detail_product_id', $transaction->detail_product_id)->exists();
            $hasPecosaRef = DetailPecosa::where('detail_product_id', $transaction->detail_product_id)->exists();
            if ($hasStock || $hasPecosaRef) {
                throw new \RuntimeException('No se puede eliminar: el lote generado por este ingreso ya tiene salidas/pecosas asociadas.');
            }

            $detailProductId = $transaction->detail_product_id;
            $transaction->delete();
            DetailProduct::where('id', $detailProductId)->delete();
        });
    }

    private function belongsToPecosa(Transaction $transaction): bool
    {
        return $transaction->document_number
            && Pecosa::where('pecosa_number', $transaction->document_number)->exists();
    }
}
