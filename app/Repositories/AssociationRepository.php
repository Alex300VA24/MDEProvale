<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssociationRepository extends BaseRepository implements AssociationRepositoryInterface
{
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
}
