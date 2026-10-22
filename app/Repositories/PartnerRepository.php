<?php

namespace App\Repositories;

use App\Models\Partner;
use App\Models\DetailProduct;
use App\Models\People;
use App\Repositories\Concerns\FiltersByNameTerms;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PartnerRepository extends BaseRepository implements PartnerRepositoryInterface
{
    use FiltersByNameTerms;

    private const DETAIL_RELATIONS = [
        'people',
        'association:id,name,code',
        'state:id,title',
        'beneficiaries.person',
        'beneficiaries.relationship:id,title',
        'beneficiaries.histories.typeBenefit:id,title,abbreviation',
        'beneficiaries.histories.state:id,title',
        'beneficiaries.histories.reasonDisqualification:id,title',
        'beneficiaries.histories.obstetricData.childBeneficiary.person',
        'documents',
    ];

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

    public function paginateForIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['partners.id', 'partners.person_id', 'partners.association_id', 'partners.state_id', 'partners.date_begin', 'partners.date_end', 'partners.observations'])
            ->with([
                'people:id,names,father_lastname,mother_lastname,dni,address',
                'association:id,name,code',
                'state:id,title',
                'beneficiaries.person',
                'beneficiaries.relationship',
                'beneficiaries.histories.typeBenefit',
                'beneficiaries.histories.state',
                'beneficiaries.histories.reasonDisqualification',
            ])
            ->withCount('beneficiaries')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('people', fn ($p) => $p->searchIdentity($search)))
            ->when($filters['association_id'] ?? null, fn ($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn ($q, $v) => $q->where('state_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function paginateForApi(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select([
                'partners.id', 'partners.person_id', 'partners.association_id', 'partners.state_id',
                'partners.date_begin', 'partners.date_end', 'partners.observations', 'partners.marital_status',
                'partners.education_level', 'partners.occupation', 'partners.children_count',
                'partners.socioeconomic_condition', 'partners.is_pregnant', 'partners.is_lactating',
                'partners.family_income',
            ])
            ->with(self::DETAIL_RELATIONS)
            ->withCount('beneficiaries')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('people', fn ($p) => $p->searchIdentity($search)))
            ->when($filters['association_id'] ?? null, fn ($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn ($q, $v) => $q->where('state_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function loadDetail(Partner $partner): Partner
    {
        return $partner->load(self::DETAIL_RELATIONS);
    }

    public function createPartner(array $data): Partner
    {
        return $this->model->create($data);
    }

    public function updatePartner(Partner $partner, array $data): bool
    {
        return $partner->update($data);
    }

    public function createPerson(array $data): People
    {
        return People::create($data);
    }

    public function deleteBeneficiaries(Partner $partner): void
    {
        $partner->beneficiaries()->delete();
    }

    public function deletePartner(Partner $partner): void
    {
        $partner->delete();
    }

    public function loadForFicha(Partner $partner): Partner
    {
        return $partner->load([
            'people.placeSector.place',
            'people.placeSector.sector',
            'association',
            'beneficiaries.person',
            'beneficiaries.relationship',
            'beneficiaries.histories' => fn ($q) => $q->orderByDesc('date_begin'),
            'beneficiaries.histories.typeBenefit',
            'beneficiaries.histories.state',
        ]);
    }

    /** Socios (con datos de su persona) cuyo nombre o DNI contiene todas las palabras dadas. */
    public function forCommitteePortal(int $associationId, string $search): Collection
    {
        return Partner::query()
            ->where('association_id', $associationId)
            ->with([
                'people:id,names,father_lastname,mother_lastname,dni,gender,telephone_number,phone_number,birthdate,address',
                'state:id,title',
                'beneficiaries.person:id,names,father_lastname,mother_lastname,dni,gender,birthdate',
                'beneficiaries.relationship:id,title',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('people', function ($q) use ($search) {
                    $q->searchIdentity($search);
                });
            })
            ->get()
            ->sortBy(fn ($partner) => $partner->people?->father_lastname)
            ->values();
    }

    public function searchByTerms(array $terms, int $limit): Collection
    {
        $query = Partner::select(['partners.id', 'partners.person_id'])
            ->join('people', 'partners.person_id', '=', 'people.id');

        $this->applyNameTerms($query, $terms, 'people.');

        return $query
            ->addSelect(['people.names', 'people.father_lastname', 'people.mother_lastname', 'people.dni'])
            ->orderBy('people.names')
            ->limit($limit)
            ->get();
    }
}
