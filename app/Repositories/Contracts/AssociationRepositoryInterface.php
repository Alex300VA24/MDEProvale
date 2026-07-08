<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface AssociationRepositoryInterface
{
    public function getActiveAssociations(?int $activeStateId = null): Collection;
    public function getAssociationsWithSectorAndBeneficiaries(?int $activeStateId = null, ?int $sectorId = null): Collection;
}