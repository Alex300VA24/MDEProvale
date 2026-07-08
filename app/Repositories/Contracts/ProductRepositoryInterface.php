<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function getDetailProductsWithStock(?int $productId = null, ?string $periodo = null): Collection;
    public function getDetailProductsByIds(Collection $ids): Collection;
}