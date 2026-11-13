<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Resolution;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;

/**
 * Bloqueo de padrones: un comité Activo cuya resolución más reciente está
 * Pendiente no puede generar padrón hasta regularizarla.
 */
class PadronGuardService
{
    public function __construct(private AssociationRepositoryInterface $associations)
    {
    }

    public function isBlocked(Association $association): bool
    {
        return $this->blockingResolution($association) !== null;
    }

    /** @throws \DomainException */
    public function assertCanGenerate(Association $association): void
    {
        $resolution = $this->blockingResolution($association);

        if ($resolution) {
            throw new \DomainException(
                'No se puede generar el padrón: la resolución '.$resolution->document
                .' del comité '.$association->name.' está Pendiente. Regularícela primero.'
            );
        }
    }

    /** @throws \DomainException si algún comité Activo tiene su última resolución Pendiente */
    public function assertNoneBlocked(): void
    {
        $this->associations->inStateWithResolutions((int) State::idFor(State::CURRENT))
            ->each(fn (Association $association) => $this->assertCanGenerate($association));
    }

    private function blockingResolution(Association $association): ?Resolution
    {
        $activeId = State::idFor(State::CURRENT);

        if (! $activeId || (int) $association->state_id !== (int) $activeId) {
            return null;
        }

        $association->loadMissing(['resolution', 'resolutionsHistory']);

        $latest = collect([$association->resolution])
            ->concat($association->resolutionsHistory)
            ->filter()
            ->unique('id')
            ->sortByDesc(fn (Resolution $r) => [$r->date_start?->timestamp ?? 0, $r->id])
            ->first();

        return $latest && $latest->isPending() ? $latest : null;
    }
}
