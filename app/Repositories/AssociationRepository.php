<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Models\AssociationStatusChange;
use App\Models\Beneficiarie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class AssociationRepository extends BaseRepository implements AssociationRepositoryInterface
{
    private const CLUB_WITH = [
        'state:id,title,abbreviation',
        'resolution:id,document,date_document,date_start,date_end,state_id',
        'resolutionsHistory:id,document,date_document,date_start,date_end,state_id',
        'placeSector:id,place_id,sector_id',
        'placeSector.place:id,title',
        'placeSector.sector:id,title',
        'typePremises:id,title',
    ];

    public function model(): string
    {
        return Association::class;
    }

    public function getActiveAssociations(?int $activeStateId = null): Collection
    {
        $query = $this->model->select(['id', 'name', 'code', 'state_id', 'resolution_id', 'address']);
        if ($activeStateId) $query->where('state_id', $activeStateId);
        return $query->get();
    }

    public function getAssociationsWithSectorAndBeneficiaries(?int $activeStateId = null, ?int $sectorId = null): Collection
    {
        return $this->model
            ->with(['placeSector.sector', 'partners.beneficiaries.person:id,birthdate'])
            ->when($activeStateId, fn($q) => $q->where('state_id', $activeStateId))
            ->when($sectorId, fn($q) => $q->whereHas('placeSector', fn($q) => $q->where('sector_id', $sectorId)))
            ->get();
    }

    public function findOrFailAssociation(int $id): Association
    {
        return $this->model->findOrFail($id);
    }

    public function findWithPlaceSector(int $id): ?Association
    {
        return $this->model->with(['placeSector.place', 'placeSector.sector'])->find($id);
    }

    public function rosterForPeriod(int $associationId, string $period): ?AssociationRosterPeriod
    {
        return AssociationRosterPeriod::with('presidentPartner.people')
            ->where('association_id', $associationId)
            ->whereDate('period', $period)
            ->first();
    }

    public function chunkWithResolutions(int $size, callable $callback): void
    {
        $this->model->with(['resolution', 'resolutionsHistory'])->chunkById($size, $callback);
    }

    public function paginateClubs(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Association::with(self::CLUB_WITH);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (! empty($filters['place_id'])) {
            $query->whereHas('placeSector', fn ($q) => $q->where('place_id', $filters['place_id']));
        }

        if (! empty($filters['sector_id'])) {
            $query->whereHas('placeSector', fn ($q) => $q->where('sector_id', $filters['sector_id']));
        }

        if (! empty($filters['place_sector_id'])) {
            $query->where('place_sector_id', $filters['place_sector_id']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function loadClubRelations(Association $association): Association
    {
        return $association->load(self::CLUB_WITH);
    }

    public function loadPartnersWithPeople(Association $association): Association
    {
        return $association->load(['partners.people:id,names,father_lastname,mother_lastname,dni']);
    }

    public function fresh(Association $association, array|string $with = []): Association
    {
        return $association->fresh($with);
    }

    public function createAssociation(array $data): Association
    {
        return Association::create($data);
    }

    public function updateAssociation(Association $association, array $data): bool
    {
        return $association->update($data);
    }

    public function deleteAssociation(Association $association): void
    {
        $association->delete();
    }

    public function idsAndCodes(): Collection
    {
        return Association::select(['id', 'code'])->get();
    }

    public function withResolutionForSelect(): Collection
    {
        return Association::whereHas('resolution')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    public function withResolutionAndLocation(): Collection
    {
        return Association::with(['placeSector.place', 'placeSector.sector'])
            ->whereHas('resolution')
            ->orderBy('code')
            ->get();
    }

    public function linkedToResolution(int $resolutionId): Collection
    {
        return Association::where('resolution_id', $resolutionId)
            ->orWhereHas('resolutionsHistory', fn ($query) => $query->whereKey($resolutionId))
            ->with(['resolution', 'resolutionsHistory'])
            ->get();
    }

    public function hasPartners(Association $association): bool
    {
        return $association->partners()->exists();
    }

    public function hasPecosas(Association $association): bool
    {
        return $association->pecosas()->exists();
    }

    public function hasResolutionHistory(Association $association): bool
    {
        return DB::table('resolution_associations')
            ->where('association_id', $association->id)
            ->exists();
    }

    public function partnerBelongs(Association $association, int $partnerId): bool
    {
        return $association->partners()->whereKey($partnerId)->exists();
    }

    public function partnerIdsAmong(Association $association, SupportCollection $partnerIds): SupportCollection
    {
        return $association->partners()->whereKey($partnerIds)->pluck('id');
    }

    public function partnerIds(Association $association): SupportCollection
    {
        return $association->partners()->pluck('id');
    }

    public function countBeneficiaries(Association $association): int
    {
        return Beneficiarie::whereIn('partner_id', $association->partners()->select('id'))->count();
    }

    public function beneficiariesWithHistory(Association $association): Collection
    {
        return Beneficiarie::with(['person', 'relationship', 'histories' => fn ($q) => $q->latest('date_begin')])
            ->whereIn('partner_id', $association->partners()->select('id'))
            ->get();
    }

    public function createStatusChange(array $data): AssociationStatusChange
    {
        return AssociationStatusChange::create($data);
    }
}
