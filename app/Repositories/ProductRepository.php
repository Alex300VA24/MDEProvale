<?php

namespace App\Repositories;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Product;
use App\Models\ProductStock;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Carbon\Carbon;
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
            ->select(['id', 'title', 'abbreviation', 'code', 'state_id', 'uom_id', 'created_at'])
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

    public function getDetailProductsByIds(\Illuminate\Support\Collection $ids): Collection
    {
        return DetailProduct::whereIn('id', $ids)
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->get()
            ->keyBy('id');
    }

    public function paginateDetailProducts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $today = now()->toDateString();

        return DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->when($filters['periodo'] ?? null, function ($q, $periodo) use ($today) {
                if ($periodo === 'vigente') {
                    $q->where('start_date', '<=', $today)->where('end_date', '>=', $today);
                } elseif ($periodo === 'vencido') {
                    $q->where('end_date', '<', $today);
                } elseif ($periodo === 'futuro') {
                    $q->where('start_date', '>', $today);
                }
            })
            ->when($filters['stock_status'] ?? null, function ($q, $status) {
                $available = '(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id))';
                if ($status === 'disponible') {
                    $q->whereRaw("$available > 0");
                } elseif ($status === 'agotado') {
                    $q->whereRaw("$available <= 0");
                }
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('product', function ($product) use ($search) {
                    $product->where('title', 'like', "{$search}%")
                        ->orWhere('abbreviation', 'like', "{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function createProduct(array $data): Product
    {
        return $this->model->create($data);
    }

    public function updateProduct(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function createDetailProduct(array $data): DetailProduct
    {
        return DetailProduct::create($data);
    }

    public function findDetailProductWithProductOrFail(int $id): DetailProduct
    {
        return DetailProduct::with('product.uom')->findOrFail($id);
    }

    public function updateDetailProduct(DetailProduct $detailProduct, array $data): bool
    {
        return $detailProduct->update($data);
    }

    public function deleteDetailProduct(int $id): void
    {
        DetailProduct::where('id', $id)->delete();
    }

    public function loadLots(Product $product): Product
    {
        return $product->load(['detailProducts' => fn ($q) => $q->orderBy('created_at', 'desc')]);
    }

    public function isReferenced(Product $product): bool
    {
        $referencedByPecosa = DetailPecosa::whereHas('detailProduct', fn ($q) => $q->where('product_id', $product->id))->exists();
        $hasStock = DetailProduct::where('product_id', $product->id)->whereHas('stocks')->exists();

        return $referencedByPecosa || $hasStock;
    }

    public function deleteWithLots(Product $product): void
    {
        DetailProduct::where('product_id', $product->id)->delete();
        $product->delete();
    }

    public function inventoryForReport(array $with = [], bool $topByTransactions = false): Collection
    {
        return $this->model
            ->with(['state', 'uom', 'detailProducts' => fn ($q) => $q->withSum('stocks as used_quantity', 'quantity')])
            ->with($with)
            ->when($topByTransactions, fn ($q) => $q->withCount('transactions')->orderBy('transactions_count', 'desc')->limit(10))
            ->get();
    }

    public function paginateLotsByPeriod(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $today = now()->toDateString();

        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['year']) || ! empty($filters['month'])) {
            $year = (int) ($filters['year'] ?? now()->year);

            if (! empty($filters['month'])) {
                $start = Carbon::create($year, (int) $filters['month'], 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
            } else {
                $start = Carbon::create($year, 1, 1)->startOfYear();
                $end = $start->copy()->endOfYear();
            }

            $query->where(function ($q) use ($start, $end) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $end->toDateString());
            })->where(function ($q) use ($start, $end) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start->toDateString());
            });
        }

        $periodo = $filters['periodo'] ?? null;
        if ($periodo === 'vigente') {
            $query->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today));
        } elseif ($periodo === 'vencido') {
            $query->whereNotNull('end_date')->where('end_date', '<', $today);
        }

        return $query->paginate($perPage);
    }

    public function loadStateAndUom(Product $product): Product
    {
        return $product->load(['state:id,title', 'uom:id,title']);
    }

    public function lotsWithUsage(): Collection
    {
        return DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    public function lotsForPecosaIndex(): Collection
    {
        return DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function ($dp) {
                $dp->available_stock = $dp->quantity - ($dp->used_quantity ?? 0);
                return $dp;
            });
    }

    public function lotsForPecosaCreate(): Collection
    {
        $lots = DetailProduct::with('product')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        $used = ProductStock::selectRaw('detail_product_id, SUM(quantity) as total_used')
            ->whereIn('detail_product_id', $lots->pluck('id'))
            ->groupBy('detail_product_id')
            ->pluck('total_used', 'detail_product_id');

        $lots->each(fn ($dp) => $dp->available_stock = $dp->quantity - ($used[$dp->id] ?? 0));

        return $lots;
    }
}
