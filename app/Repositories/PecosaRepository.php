<?php

namespace App\Repositories;

use App\Models\Directive;
use App\Models\Pecosa;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PecosaRepository extends BaseRepository implements PecosaRepositoryInterface
{
    public function model(): string
    {
        return Pecosa::class;
    }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'pecosa_number', 'delivery_date', 'observation', 'managing_partner_id', 'president_name', 'state_id', 'association_id', 'chief_name', 'storekeeper_name', 'created_at'])
            ->with(['association:id,name,code,address,state_id', 'state:id,title,abbreviation', 'managingPartner.people:id,names,father_lastname,mother_lastname,dni', 'detailPecosas:id,pecosa_id,detail_product_id,quantity,unit_price,subtotal'])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('pecosa_number', 'like', "{$v}%"))
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '<=', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findByAssociationAndPeriod(int $associationId, string $startDate, string $endDate): ?Pecosa
    {
        return $this->model
            ->with('detailPecosas.detailProduct.product')
            ->where('association_id', $associationId)
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->first();
    }

    public function getPresidentDirectivesByAssociation(Collection $associationIds, int $presidentPositionId, int $activeStateId): Collection
    {
        return Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id'])
            ->where('position_id', $presidentPositionId)
            ->where('state_id', $activeStateId)
            ->whereHas('partner', fn($q) => $q->whereIn('association_id', $associationIds))
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get()
            ->mapToGroups(fn($d) => [$d->partner->association_id => $d])
            ->map(fn($c) => $c->first());
    }
}