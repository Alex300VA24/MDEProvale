<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\ProductStock;
use Illuminate\Support\Collection;

class StockService
{
    public function getAvailableStockByProduct(int $productId): int
    {
        return DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get()
            ->sum(fn($dp) => $dp->quantity - ($dp->used_quantity ?? 0));
    }

    public function getAvailableStockByDetailProduct(int $detailProductId): int
    {
        $dp = DetailProduct::withSum('stocks as used_quantity', 'quantity')->findOrFail($detailProductId);
        return $dp->quantity - ($dp->used_quantity ?? 0);
    }

    public function deductByDetailProduct(int $detailProductId, float $quantity, ?int $pecosaId = null, ?int $transactionId = null, string $observation = 'Salida por Pecosa'): ProductStock
    {
        $available = $this->getAvailableStockByDetailProduct($detailProductId);
        if ($quantity > $available) {
            throw new \RuntimeException("Stock insuficiente. Disponible: {$available}, Solicitado: {$quantity}");
        }

        return ProductStock::create([
            'detail_product_id' => $detailProductId,
            'pecosa_id' => $pecosaId,
            'transaction_id' => $transactionId,
            'quantity' => $quantity,
            'observation' => $observation . ($pecosaId ? " #{$pecosaId}" : ''),
        ]);
    }

    public function deductByProduct(int $productId, float $quantity, ?int $pecosaId = null, ?int $transactionId = null): void
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

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
        ProductStock::where('pecosa_id', $pecosaId)->delete();
    }

    public function revertStockByDetailProduct(int $detailProductId): void
    {
        ProductStock::where('detail_product_id', $detailProductId)->delete();
    }

    public function revertStockByTransaction(int $transactionId): void
    {
        ProductStock::where('transaction_id', $transactionId)->delete();
    }
}
