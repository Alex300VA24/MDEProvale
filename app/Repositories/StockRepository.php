<?php

namespace App\Repositories;

use App\Models\DetailProduct;
use App\Models\ProductStock;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function currentLotsWithUsage(int $productId, bool $fifo = false): Collection
    {
        $today = now()->toDateString();

        return DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->withSum('stocks as used_quantity', 'quantity')
            ->when($fifo, fn ($q) => $q->orderBy('start_date', 'asc')->orderBy('id', 'asc'))
            ->get();
    }

    public function findLotWithUsageOrFail(int $detailProductId): DetailProduct
    {
        return DetailProduct::withSum('stocks as used_quantity', 'quantity')->findOrFail($detailProductId);
    }

    public function createStock(array $data): ProductStock
    {
        return ProductStock::create($data);
    }

    public function deleteByPecosa(int $pecosaId): void
    {
        ProductStock::where('pecosa_id', $pecosaId)->delete();
    }

    public function deleteByDetailProduct(int $detailProductId): void
    {
        ProductStock::where('detail_product_id', $detailProductId)->delete();
    }

    public function deleteByTransaction(int $transactionId): void
    {
        ProductStock::where('transaction_id', $transactionId)->delete();
    }

    public function existsForDetailProduct(int $detailProductId): bool
    {
        return ProductStock::where('detail_product_id', $detailProductId)->exists();
    }
}
