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

    public function deductByDetailProduct(int $detailProductId, float $quantity, ?int $pecosaId = null, string $observation = 'Salida por Pecosa'): ProductStock
    {
        $available = $this->getAvailableStockByDetailProduct($detailProductId);

        if ($quantity > $available) {
            throw new \RuntimeException("Stock insuficiente. Disponible: {$available}, Solicitado: {$quantity}");
        }

        return ProductStock::create([
            'detail_product_id' => $detailProductId,
            'pecosa_id' => $pecosaId,
            'quantity' => $quantity,
            'observation' => $observation . ($pecosaId ? " #{$pecosaId}" : ''),
        ]);
    }

    public function deductByProduct(int $productId, float $quantity, ?int $pecosaId = null): void
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($detailProducts as $dp) {
            if ($remaining <= 0) break;
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            if ($available > 0) {
                $deduct = min($remaining, $available);
                ProductStock::create([
                    'detail_product_id' => $dp->id,
                    'pecosa_id' => $pecosaId,
                    'quantity' => $deduct,
                    'observation' => 'Salida por transacción' . ($pecosaId ? " - Pecosa #{$pecosaId}" : ''),
                ]);
                $remaining -= $deduct;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stock insuficiente. Faltan {$remaining} unidades.");
        }
    }

    public function revertStockByPecosa(int $pecosaId): void
    {
        ProductStock::where('pecosa_id', $pecosaId)->delete();
    }

    public function getStockInfoByProduct(int $productId): array
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get();

        $totalStock = 0;
        $totalValue = 0;

        foreach ($detailProducts as $dp) {
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            $totalStock += $available;
            $totalValue += $available * $dp->unit_price;
        }

        return [
            'quantity' => $totalStock,
            'unit_price' => $totalStock > 0 ? $totalValue / $totalStock : 0,
            'total' => $totalValue,
        ];
    }
}