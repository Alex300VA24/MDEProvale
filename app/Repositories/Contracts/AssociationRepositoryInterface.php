<?php

namespace App\Repositories\Contracts;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use Illuminate\Database\Eloquent\Collection;

interface AssociationRepositoryInterface
{
    public function getActiveAssociations(?int $activeStateId = null): Collection;
    public function getAssociationsWithSectorAndBeneficiaries(?int $activeStateId = null, ?int $sectorId = null): Collection;

    public function findOrFailAssociation(int $id): Association;

    public function findWithPlaceSector(int $id): ?Association;

    /** Padrón mensual del comité para el período (primer día del mes). */
    public function rosterForPeriod(int $associationId, string $period): ?AssociationRosterPeriod;

    /** Recorre todos los comités con su resolución e historial en bloques. */
    public function chunkWithResolutions(int $size, callable $callback): void;
}
