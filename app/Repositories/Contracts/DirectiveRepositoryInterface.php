<?php

namespace App\Repositories\Contracts;

use App\Models\Association;
use App\Models\Directive;
use App\Models\Position;
use Illuminate\Support\Collection;

/**
 * Cargos de la directiva de un comité.
 */
interface DirectiveRepositoryInterface
{
    /** Resoluciones del comité: la vigente más las del historial. */
    public function committeeResolutionIds(Association $association): Collection;

    public function firstOrCreatePosition(string $title): Position;

    /** Cargos por título, indexados por título. */
    public function positionsByTitles(Collection|array $titles): Collection;

    /** Cierra los cargos vigentes de ese cargo para las socias y resoluciones indicadas. */
    public function expireCurrent(Collection $partnerIds, Collection $resolutionIds, int $positionId, int $currentStateId, int $expiredStateId, string $dateEnd): int;

    public function create(array $data): Directive;

    public function hasForResolution(int $resolutionId): bool;

    /**
     * Cargos vigentes de la directiva del comité: título del cargo => id de socia.
     *
     * @return array<string, int>
     */
    public function currentCommitteePositions(Collection $partnerIds, int $currentStateId): array;

    /**
     * Cargo vigente de presidenta de la persona con ese DNI, con su comité cargado.
     */
    public function currentPresidentAssignment(string $dni, string $today): ?Directive;
}
