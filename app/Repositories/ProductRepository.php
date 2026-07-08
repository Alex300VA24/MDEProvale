<?php

namespace App\Repositories;

use App\Models\DetailProduct;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function model(): string
    {
        return Product::class;
    }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'title', 'abbreviation', 'state_id', 'uom_id', 'created_at'])
            ->with(['state:id,title,abbreviation', 'uom:id,title', 'detailProducts' => function ($q) {
                $q->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
                  ->withSum('stocks as used_quantity', 'quantity');
            }])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(fn($q) => $q->where('title', 'like', "{$search}%")->orWhere('abbreviation', 'like', "{$search}%"));
            })
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->when($filters['uom_id'] ?? null, fn($q, $v) => $q->where('uom_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getDetailProductsWithStock(?int $productId = null, ?string $periodo = null): Collection
    {
        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id'])
            ->withSum('stocks as used_quantity', 'quantity');

        if ($productId) $query->where('product_id', $productId);

        if ($periodo) {
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', $today)->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', $today);
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', $today);
            }
        }

        return $query->orderBy('start_date', 'asc')->get()
            ->map(fn($dp) => $dp->setAttribute('available_stock', $dp->quantity - ($dp->used_quantity ?? 0)));
    }

    public function getDetailProductsByIds(Collection $ids): Collection
    {
        return DetailProduct::whereIn('id', $ids)
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->get()
            ->keyBy('id');
    }
}