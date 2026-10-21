<?php

namespace App\Repositories\Contracts;

use App\Models\DetailProduct;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface
{
    /** Lotes vigentes hoy del producto, con `used_quantity`; ordenados FIFO si $fifo. */
    public function currentLotsWithUsage(int $productId, bool $fifo = false): Collection;

    public function findLotWithUsageOrFail(int $detailProductId): DetailProduct;

    public function createStock(array $data): ProductStock;

    public function deleteByPecosa(int $pecosaId): void;

    public function deleteByDetailProduct(int $detailProductId): void;

    public function deleteByTransaction(int $transactionId): void;

    public function existsForDetailProduct(int $detailProductId): bool;
}
