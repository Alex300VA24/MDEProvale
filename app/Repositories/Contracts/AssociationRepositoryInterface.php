<?php

namespace App\Repositories\Contracts;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Models\AssociationStatusChange;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

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

    /** Filtros: search (nombre o código), state_id, place_id, sector_id, place_sector_id. */
    public function paginateClubs(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Filtros: search (nombre o código), state_id, resolution_id. Incluye socias con su persona. */

    /** Padrones mensuales del primer día del mes, indexados por comité. */
    public function rosterPeriodsForMonth(string $firstDay): SupportCollection;

    /**
     * Comités del padrón histórico: los del snapshot mensual (`$rosterIds`) o, sin snapshot,
     * los cuya resolución cubre el rango.
     */
    public function forPadron(?SupportCollection $rosterIds, string $start, string $end): Collection;

    /** Beneficiarios con historial vigente en el rango, de las socias del comité vigentes en él. */
    public function beneficiaryCountInPeriod(int $associationId, string $start, string $end): int;

    /** Carga estado, resoluciones, ubicación y local que expone el recurso del comité. */
    public function loadClubRelations(Association $association): Association;

    public function loadPartnersWithPeople(Association $association): Association;

    /** Copia del comité recargada desde la base, con las relaciones indicadas. */
    public function fresh(Association $association, array|string $with = []): Association;

    public function createAssociation(array $data): Association;

    public function updateAssociation(Association $association, array $data): bool;

    public function deleteAssociation(Association $association): void;

    /** Id y código de todos los comités. */
    public function idsAndCodes(): Collection;

    /** Comités que tienen una resolución vigente, por código. */
    public function withResolutionForSelect(): Collection;

    /** Comités con resolución, con ubicación, por código. */
    public function withResolutionAndLocation(): Collection;

    /** Comités vinculados a la resolución, como principal o por historial. */
    public function linkedToResolution(int $resolutionId): Collection;

    public function hasPartners(Association $association): bool;

    public function hasPecosas(Association $association): bool;

    public function hasResolutionHistory(Association $association): bool;

    public function partnerBelongs(Association $association, int $partnerId): bool;

    /** Ids de socias del comité, entre los indicados. */
    public function partnerIdsAmong(Association $association, SupportCollection $partnerIds): SupportCollection;

    public function partnerIds(Association $association): SupportCollection;

    public function countBeneficiaries(Association $association): int;

    /** Beneficiarios del comité con persona, parentesco e historiales, para evaluación nutricional. */
    public function beneficiariesWithHistory(Association $association): Collection;

    public function createStatusChange(array $data): AssociationStatusChange;
}
