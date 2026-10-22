<?php

namespace App\Repositories;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Person;
use App\Models\People;
use App\Models\Responsible;
use App\Models\Transaction;
use App\Repositories\Contracts\ControlInternoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ControlInternoRepository implements ControlInternoRepositoryInterface
{
    public function detailProductIdsByKeywords(array $keywords): array
    {
        return DetailProduct::query()
            ->whereHas('product', function ($q) use ($keywords) {
                $q->where(function ($inner) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $inner->orWhereRaw('UPPER(title) LIKE ?', ["%{$keyword}%"]);
                    }
                });
            })
            ->pluck('id')
            ->all();
    }

    public function ingresosBetween(array $detailProductIds, string $from, string $to): Collection
    {
        return Transaction::query()
            ->whereIn('detail_product_id', $detailProductIds)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('quantity', '>', 0)
            ->whereHas('typeTransaction', fn ($q) => $q->whereRaw('LOWER(TRIM(title)) = ?', ['ingreso']))
            ->with('detailProduct:id,start_date,end_date')
            ->orderBy('transaction_date')->orderBy('id')
            ->get();
    }

    public function lotsStartingBetween(array $detailProductIds, string $from, string $to): Collection
    {
        return DetailProduct::query()
            ->whereIn('id', $detailProductIds)
            ->whereBetween('start_date', [$from, $to])
            ->where('quantity', '>', 0)
            ->orderBy('start_date')->orderBy('id')
            ->get();
    }

    public function pecosaDetailsForPeriod(array $detailProductIds, int $year, int $month): Collection
    {
        return DetailPecosa::query()
            ->whereIn('detail_product_id', $detailProductIds)
            ->whereHas('pecosa', fn ($q) => $q->forDeliveryPeriod($year, $month))
            ->with('pecosa:id,delivery_date')
            ->get();
    }

    public function responsibles(): Collection
    {
        return Responsible::query()->with('person')->get();
    }

    public function activeStorekeeper(): ?People
    {
        return Responsible::query()
            ->where('type', 'storekeeper')
            ->where('active', true)
            ->with('person')
            ->latest('start_date')
            ->first()?->person;
    }
}
