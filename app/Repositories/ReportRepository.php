<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Pecosa;
use App\Models\PlaceSector;
use App\Models\Product;
use App\Models\Relationship;
use App\Models\State;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository implements ReportRepositoryInterface
{
    public function placeSectors(): Collection
    {
        return PlaceSector::with(['place:id,title', 'sector:id,title'])->get();
    }

    public function associationStates(): Collection
    {
        return State::forAssociations()->get(['id', 'title as name']);
    }

    public function productStates(): Collection
    {
        return State::temporal()->get(['id', 'title as name']);
    }

    public function associations(): Collection
    {
        return Association::orderBy('name')->get(['id', 'name', 'code']);
    }

    public function relationships(): Collection
    {
        return Relationship::orderBy('title')->get(['id', 'title as name']);
    }

    public function typeTransactions(): Collection
    {
        return TypeTransaction::orderBy('title')->get(['id', 'title as name']);
    }

    public function committees(array $filters): Collection
    {
        $query = Association::with(['placeSector.place', 'placeSector.sector', 'state', 'partners.people']);

        if (!empty($filters['place_sector_id'])) {
            $query->where('place_sector_id', $filters['place_sector_id']);
        }
        if (!empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        $associations = $query->orderBy('code')->get();
        Association::hydratePresidents($associations);

        return $associations;
    }

    public function beneficiaries(array $filters): Collection
    {
        $query = Beneficiarie::with(['person', 'partner.people', 'partner.association', 'relationship']);

        if (!empty($filters['relationship_id'])) {
            $query->where('relationship_id', $filters['relationship_id']);
        }
        if (!empty($filters['association_id'])) {
            $query->whereHas('partner', fn ($q) => $q->where('association_id', $filters['association_id']));
        }

        return $query->get();
    }

    public function products(array $filters): Collection
    {
        $query = Product::with(['uom', 'state', 'detailProducts.stocks']);

        if (!empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        return $query->orderBy('title')->get();
    }

    public function pecosas(array $filters): Collection
    {
        $query = Pecosa::with(['association', 'state']);

        if (!empty($filters['association_id'])) {
            $query->where('association_id', $filters['association_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('delivery_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('delivery_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('delivery_date')->get();
    }

    public function transactions(array $filters): Collection
    {
        $query = Transaction::with(['detailProduct.product', 'typeTransaction']);

        if (!empty($filters['type_transaction_id'])) {
            $query->where('type_transaction_id', $filters['type_transaction_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('transaction_date')->get();
    }
}
