<?php

namespace App\Repositories;

use App\Models\Partner;
use App\Models\DetailProduct;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use Carbon\Carbon;
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
                    $q->searchIdentity($search);
                });
            })
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findActiveByAssociation(int $associationId, string $startDate, string $endDate): Collection
    {
        $period = Carbon::parse($startDate)->startOfMonth()->toDateString();

        return $this->model
            ->where('association_id', $associationId)
            ->where(function ($query) use ($period, $startDate, $endDate) {
                $query->whereHas('rosterPeriods', fn ($periods) => $periods->whereDate('period', $period))
                    ->orWhere(function ($legacy) use ($startDate, $endDate) {
                        $legacy->whereDoesntHave('rosterPeriods')
                            ->where(function ($dates) use ($endDate) {
                                $dates->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate);
                            })
                            ->where(function ($dates) use ($startDate) {
                                $dates->whereNull('date_end')->orWhere('date_end', '>=', $startDate);
                            });
                    });
            })
            ->with([
                'people',
                'beneficiaries.person',
                'beneficiaries.relationship',
                'beneficiaries.histories.typeBenefit',
                'beneficiaries.histories.relationship',
                'beneficiaries.histories.reasonDisqualification',
            ])
            ->get();
    }

    public function countBeneficiariesForAssociationAtDate(int $associationId, string $date): int
    {
        $period = Carbon::parse($date)->startOfMonth()->toDateString();

        return $this->model
            ->where('association_id', $associationId)
            ->where(function ($query) use ($period, $date) {
                $query->whereHas('rosterPeriods', fn ($periods) => $periods->whereDate('period', $period))
                    ->orWhere(function ($legacy) use ($date) {
                        $legacy->whereDoesntHave('rosterPeriods')
                            ->where(fn ($dates) => $dates->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
                            ->where(fn ($dates) => $dates->whereNull('date_end')->orWhere('date_end', '>=', $date));
                    });
            })
            ->withCount(['beneficiaries as historical_count' => function ($query) use ($date) {
                $query->where(fn ($beneficiaries) => $beneficiaries->whereDoesntHave('histories')
                    ->orWhereHas('histories', function ($histories) use ($date) {
                        $histories->where(fn ($dates) => $dates->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
                            ->where(fn ($dates) => $dates->whereNull('date_end')->orWhere('date_end', '>=', $date));
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
