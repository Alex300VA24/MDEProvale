<?php

namespace App\Services;

use App\Models\Transaction;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepo,
        private ProductRepositoryInterface $productRepo,
        private StockRepositoryInterface $stockRepo,
        private PecosaRepositoryInterface $pecosaRepo,
        private CatalogRepositoryInterface $catalogs,
        private StockService $stockService
    ) {
    }

    public function searchTransactions(array $filters, int $perPage = 15)
    {
        return $this->transactionRepo->searchTransactions($filters, $perPage);
    }

    /** Datos del listado de movimientos con sus catálogos de filtro. */
    public function indexData(array $filters): array
    {
        return [
            'transactions' => $this->transactionRepo->searchTransactions($filters),
            'types' => $this->catalogs->typeTransactions(),
            'products' => $this->catalogs->products(),
        ];
    }

    public function registerIngreso(array $data): Transaction
    {
        $typeIngreso = $this->transactionRepo->findTypeByTitleOrFail('ingreso');

        return DB::transaction(function () use ($data, $typeIngreso) {
            $detailProduct = $this->productRepo->createDetailProduct([
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addYear()->toDateString(),
            ]);

            $detailProduct->load('product.uom');

            return $this->transactionRepo->create([
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
        $typeSalida = $this->transactionRepo->findTypeByTitleOrFail('salida');
        $detailProduct = $this->productRepo->findDetailProductWithProductOrFail($data['detail_product_id']);

        return DB::transaction(function () use ($data, $typeSalida, $detailProduct) {
            $transaction = $this->transactionRepo->create([
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
            $transaction = $this->transactionRepo->freshWithType($transaction);

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
                $this->transactionRepo->update($transaction, $updates);

                $detailProduct = $this->productRepo->findDetailProductWithProductOrFail($transaction->detail_product_id);
                $this->stockService->deductByProduct($detailProduct->product_id, $quantity, null, $transaction->id);

                return $transaction->fresh();
            }

            // Ingreso: actualiza el lote (DetailProduct) validando que no quede negativo.
            $detailProduct = $this->stockRepo->findLotWithUsageOrFail($transaction->detail_product_id);
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
            $this->productRepo->updateDetailProduct($detailProduct, $loteUpdates);
            $this->transactionRepo->update($transaction, $updates);

            return $transaction->fresh();
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = $this->transactionRepo->freshWithType($transaction);

            if ($transaction->typeTransaction && $transaction->typeTransaction->isSalida()) {
                if ($this->belongsToPecosa($transaction)) {
                    throw new \RuntimeException('No se puede eliminar: el movimiento pertenece a una Pecosa. Elimine la Pecosa en el módulo de Pecosas.');
                }

                $this->stockService->revertStockByTransaction($transaction->id);
                $this->transactionRepo->delete($transaction);
                return;
            }

            // Ingreso: elimina el lote creado, validando dependencias.
            $hasStock = $this->stockRepo->existsForDetailProduct($transaction->detail_product_id);
            $hasPecosaRef = $this->pecosaRepo->lotIsInAnyPecosa($transaction->detail_product_id);
            if ($hasStock || $hasPecosaRef) {
                throw new \RuntimeException('No se puede eliminar: el lote generado por este ingreso ya tiene salidas/pecosas asociadas.');
            }

            $detailProductId = $transaction->detail_product_id;
            $this->transactionRepo->delete($transaction);
            $this->productRepo->deleteDetailProduct($detailProductId);
        });
    }

    /** Tipos de transacción para formularios (resuelve ingreso/salida por id). */
    public function isIngresoType(int $typeTransactionId): bool
    {
        $type = $this->transactionRepo->findTypeById($typeTransactionId);

        return $type && $type->isIngreso();
    }

    private function belongsToPecosa(Transaction $transaction): bool
    {
        return $transaction->document_number
            && $this->pecosaRepo->numberExists($transaction->document_number);
    }

    /** Registra un ingreso o una salida según el tipo de movimiento. */
    public function register(array $data): Transaction
    {
        $type = $this->transactionRepo->findTypeById((int) $data['type_transaction_id']);

        return $type && $type->isIngreso()
            ? $this->registerIngreso($data)
            : $this->registerSalida($data);
    }

    /** Catálogos del formulario de movimientos: tipos, productos, lotes vigentes y años. */
    public function options(): array
    {
        $years = $this->transactionRepo->transactionDates()
            ->map(fn ($date) => (int) \Carbon\Carbon::parse($date)->format('Y'))
            ->push((int) now()->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $detailProducts = $this->productRepo->currentLotsWithUsage(now()->toDateString())
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

        return [
            'types' => $this->catalogs->typeTransactions(['id', 'title']),
            'products' => $this->catalogs->products(['id', 'title', 'abbreviation']),
            'detail_products' => $detailProducts,
            'years' => $years,
        ];
    }
}
