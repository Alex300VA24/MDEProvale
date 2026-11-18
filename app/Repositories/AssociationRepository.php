<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Models\AssociationStatusChange;
use App\Models\State;
use App\Models\Beneficiarie;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class AssociationRepository extends BaseRepository implements AssociationRepositoryInterface
{
    private const CLUB_WITH = [
        'state:id,title,abbreviation',
        'resolution:id,document,date_document,date_start,date_end,state_id',
        'resolution.state:id,title,abbreviation',
        'resolutionsHistory:id,document,date_document,date_start,date_end,state_id',
        'resolutionsHistory.state:id,title,abbreviation',
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
            ->with(['state', 'placeSector.sector', 'partners.beneficiaries.person:id,birthdate'])
            ->when($activeStateId, fn($q) => $q->where('state_id', $activeStateId))
            ->when($sectorId, fn($q) => $q->whereHas('placeSector', fn($q) => $q->where('sector_id', $sectorId)))
            ->get();
    }

    public function searchByName(string $term): Collection
    {
        return $this->model
            ->with(['resolution', 'resolutionsHistory'])
            ->where('name', 'like', '%'.trim($term).'%')
            ->orderBy('name')
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

    public function inStateWithResolutions(int $stateId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where('state_id', $stateId)->with(['resolution', 'resolutionsHistory'])->get();
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

        return $query->orderByRaw('CHAR_LENGTH(code)')->orderBy('code')->paginate($perPage);
    }


    public function rosterPeriodsForMonth(string $firstDay): SupportCollection
    {
        return AssociationRosterPeriod::query()
            ->whereDate('period', $firstDay)
            ->get()
            ->keyBy('association_id');
    }

    public function forPadron(?SupportCollection $rosterIds, string $start, string $end): Collection
    {
        $query = Association::with([
            'placeSector.place',
            'placeSector.sector',
            'resolution',
            'resolutionsHistory',
            'state',
            'typePremises',
        ]);

        if ($rosterIds !== null) {
            return $query->whereIn('id', $rosterIds)->get();
        }

        return $query->whereHas('resolution', function ($q) use ($start, $end) {
            $q->where('date_start', '<=', $end)
                ->where(function ($q2) use ($start) {
                    $q2->whereNull('date_end')
                        ->orWhere('date_end', '>=', $start);
                });
        })->get();
    }

    public function beneficiaryCountInPeriod(int $associationId, string $start, string $end): int
    {
        return (int) Partner::where('association_id', $associationId)
            ->where(function ($q) use ($end) {
                $q->whereNull('date_begin')
                    ->orWhere('date_begin', '<=', $end);
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('date_end')
                    ->orWhere('date_end', '>=', $start);
            })
            ->withCount(['beneficiaries as benef_count' => function ($q) use ($start, $end) {
                $q->whereHas('histories', function ($h) use ($start, $end) {
                    $h->where('date_begin', '<=', $end)
                        ->where(function ($h2) use ($start) {
                            $h2->whereNull('date_end')
                                ->orWhere('date_end', '>=', $start);
                        });
                });
            }])
            ->get()
            ->sum('benef_count');
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
        // Limpia su historial de resoluciones (tabla pivote) antes de borrar el comité.
        $association->resolutionsHistory()->detach();
        $association->delete();
    }

    public function idsAndCodes(): Collection
    {
        return Association::select(['id', 'code'])->get();
    }

    public function withResolutionForSelect(): Collection
    {
        return Association::with('state:id,abbreviation')
            ->whereHas('resolution')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'state_id'])
            ->each(fn (Association $association) => $association->setAttribute('active', $association->isHabilitado()));
    }

    public function withResolutionAndLocation(): Collection
    {
        return Association::with(['placeSector.place', 'placeSector.sector', 'state'])
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

    public function partnerIsActive(Association $association, int $partnerId): bool
    {
        $today = now()->toDateString();

        return $association->partners()
            ->whereKey($partnerId)
            ->where(fn ($q) => $q->whereNull('date_end')->orWhereDate('date_end', '>=', $today))
            ->exists();
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

    public function countBeneficiariesActiveDuring(Association $association, string $startDate, string $endDate): int
    {
        return Beneficiarie::activeDuring($startDate, $endDate)
            ->whereIn('partner_id', $association->partners()->select('id'))
            ->count();
    }

    /** ¿El comité estaba vigente al cierre de la fecha dada? Reconstruye el estado con el historial de cambios. */
    public function wasHabilitadoAt(Association $association, string $date): bool
    {
        $vigenteId = State::where('abbreviation', State::CURRENT)->value('id');
        $changes = $association->statusChanges()->orderBy('effective_date')->orderBy('id')->get();

        $stateId = $association->state_id;
        $applied = $changes->filter(fn ($c) => $c->effective_date->toDateString() <= $date)->last();
        if ($applied) {
            $stateId = $applied->to_state_id;
        } elseif ($changes->isNotEmpty()) {
            $stateId = $changes->first()->from_state_id ?? $association->state_id;
        }

        return $stateId !== null && (int) $stateId === (int) $vigenteId;
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
