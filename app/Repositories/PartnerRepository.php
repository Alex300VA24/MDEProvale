<?php

namespace App\Repositories;

use App\Models\Partner;
use App\Models\DetailProduct;
use App\Models\State;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PartnerRepository extends BaseRepository implements PartnerRepositoryInterface
{
    public function model(): string
    {
        return Partner::class;
    }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'person_id', 'association_id', 'state_id', 'date_begin', 'date_end', 'observations'])
            ->with(['people:id,names,father_lastname,mother_lastname,dni', 'association:id,name,code', 'state:id,title'])
            ->withCount('beneficiaries')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('people', function ($q) use ($search) {
                    $q->where('names', 'like', "%{$search}%")
                      ->orWhere('father_lastname', 'like', "%{$search}%")
                      ->orWhere('mother_lastname', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                });
            })
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * `date_begin`/`date_end` no están poblados en la data migrada (el sistema de
     * origen solo trackea vigencia por `state_id`, no por rango de fechas), así que
     * un socio sin fechas se considera vigente en cualquier periodo, igual que en
     * countBeneficiariesForAssociationAtDate().
     */
    public function findActiveByAssociation(int $associationId, string $date): Collection
    {
        return $this->model
            ->where('association_id', $associationId)
            ->where(function ($q) use ($date) {
                $q->whereNull('date_begin')->orWhere('date_begin', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('date_end')->orWhere('date_end', '>=', $date);
            })
            ->with(['people', 'beneficiaries.person', 'beneficiaries.relationship', 'beneficiaries.histories.typeBenefit', 'beneficiaries.histories.reasonDisqualification'])
            ->get();
    }

    public function countBeneficiariesForAssociationAtDate(int $associationId, string $date): int
    {
        $activeStateIds = State::whereIn('abbreviation', ['A', 'ACTI'])
            ->orWhereRaw('LOWER(title) = ?', ['activo'])
            ->pluck('id');

        return $this->model
            ->where('association_id', $associationId)
            ->when($activeStateIds->isNotEmpty(), fn($q) => $q->whereIn('state_id', $activeStateIds))
            ->where(fn($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
            ->where(fn($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $date))
            ->withCount(['beneficiaries as historical_count' => function ($q) use ($activeStateIds, $date) {
                $q->where(fn($q) => $q->whereDoesntHave('histories')->orWhereHas('histories', function ($h) use ($activeStateIds, $date) {
                    $h->when($activeStateIds->isNotEmpty(), fn($q) => $q->whereIn('state_id', $activeStateIds))
                      ->where(fn($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
                      ->where(fn($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $date));
                }));
            }])
            ->get()
            ->sum('historical_count');
    }

    public function getDetailProductsByIds(Collection $ids): Collection
    {
        return DetailProduct::whereIn('id', $ids)
            ->with(['product:id,title,abbreviation,state_id,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->get()
            ->keyBy('id');
    }
}