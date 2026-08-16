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
            ->select([
                'id', 'pecosa_number', 'delivery_date', 'observation',
                'association_id', 'state_id', 'managing_partner_id', 'chief_id', 'storekeeper_id',
                'president_name', 'president_dni', 'chief_name', 'chief_dni',
                'storekeeper_name', 'storekeeper_dni', 'managing_partner_name', 'managing_partner_dni',
                'association_name', 'association_code', 'association_address',
                'association_zone_code', 'association_zone_name', 'association_sector_name',
                'beneficiaries_count', 'created_at',
            ])
            ->with([
                'association:id,name,code,address,state_id',
                'state:id,title,abbreviation',
                'managingPartner.people:id,names,father_lastname,mother_lastname,dni',
                'chief.person:id,names,father_lastname,mother_lastname,dni',
                'storekeeper.person:id,names,father_lastname,mother_lastname,dni',
                'detailPecosas:id,pecosa_id,detail_product_id,quantity,unit_price,subtotal,product_name,product_abbreviation,uom_title',
                'detailPecosas.detailProduct:id,product_id,unit_price,start_date,end_date',
                'detailPecosas.detailProduct.product:id,title,abbreviation',
            ])
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

    public function getPresidentDirectivesByAssociation(\Illuminate\Support\Collection $associationIds, int $presidentPositionId, int $activeStateId): \Illuminate\Support\Collection
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
