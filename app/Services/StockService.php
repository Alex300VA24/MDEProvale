<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Repositories\Contracts\StockRepositoryInterface;

class StockService
{
    public function __construct(private StockRepositoryInterface $stockRepo)
    {
    }

    public function getAvailableStockByProduct(int $productId): int
    {
        return $this->stockRepo->currentLotsWithUsage($productId)
            ->sum(fn($dp) => $dp->quantity - ($dp->used_quantity ?? 0));
    }

    public function getAvailableStockByDetailProduct(int $detailProductId): int
    {
        $dp = $this->stockRepo->findLotWithUsageOrFail($detailProductId);
        return $dp->quantity - ($dp->used_quantity ?? 0);
    }

    public function deductByDetailProduct(int $detailProductId, float $quantity, ?int $pecosaId = null, ?int $transactionId = null, string $observation = 'Salida por Pecosa'): ProductStock
    {
        $available = $this->getAvailableStockByDetailProduct($detailProductId);
        if ($quantity > $available) {
            throw new \RuntimeException("Stock insuficiente. Disponible: {$available}, Solicitado: {$quantity}");
        }

        return $this->stockRepo->createStock([
            'detail_product_id' => $detailProductId,
            'pecosa_id' => $pecosaId,
            'transaction_id' => $transactionId,
            'quantity' => $quantity,
            'observation' => $observation . ($pecosaId ? " #{$pecosaId}" : ''),
        ]);
    }

    public function deductByProduct(int $productId, float $quantity, ?int $pecosaId = null, ?int $transactionId = null): void
    {
        $detailProducts = $this->stockRepo->currentLotsWithUsage($productId, true);

        $remaining = $quantity;
        foreach ($detailProducts as $dp) {
            if ($remaining <= 0) {
                break;
            }

            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            $toDeduct = min($remaining, $available);
            if ($toDeduct <= 0) {
                continue;
            }

            $this->deductByDetailProduct($dp->id, $toDeduct, $pecosaId, $transactionId);
            $remaining -= $toDeduct;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stock insuficiente. Faltan {$remaining} unidades.");
        }
    }

    public function revertStockByPecosa(int $pecosaId): void
    {
        $this->stockRepo->deleteByPecosa($pecosaId);
    }

    public function revertStockByDetailProduct(int $detailProductId): void
    {
        $this->stockRepo->deleteByDetailProduct($detailProductId);
    }

    public function revertStockByTransaction(int $transactionId): void
    {
        $this->stockRepo->deleteByTransaction($transactionId);
    }
}
