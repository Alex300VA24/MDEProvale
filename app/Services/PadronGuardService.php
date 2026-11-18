<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Resolution;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use Carbon\Carbon;

/**
 * Una resolución Pendiente solo bloquea el padrón de su mismo mes. Los
 * padrones históricos anteriores permanecen consultables e inmutables.
 */
class PadronGuardService
{
    public function __construct(private AssociationRepositoryInterface $associations)
    {
    }

    public function isBlocked(Association $association, int $year, int $month): bool
    {
        return $this->blockingResolution($association, $year, $month) !== null;
    }

    /** @throws \DomainException */
    public function assertCanGenerate(Association $association, int $year, int $month): void
    {
        $resolution = $this->blockingResolution($association, $year, $month);

        if ($resolution) {
            throw new \DomainException(
                'No se puede generar el padrón: la resolución '.$resolution->document
                .' del comité '.$association->name.' está Pendiente para el período '
                .sprintf('%04d-%02d', $year, $month).'. Regularícela primero.'
            );
        }
    }

    /** @throws \DomainException si algún comité activo tiene una resolución pendiente del período */
    public function assertNoneBlockedForPeriod(int $year, int $month): void
    {
        $this->associations->inStateWithResolutions((int) State::idFor(State::CURRENT))
            ->each(fn (Association $association) => $this->assertCanGenerate($association, $year, $month));
    }

    private function blockingResolution(Association $association, int $year, int $month): ?Resolution
    {
        $activeId = State::idFor(State::CURRENT);

        if (! $activeId || (int) $association->state_id !== (int) $activeId) {
            return null;
        }

        $association->loadMissing(['resolution', 'resolutionsHistory']);

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $latest = collect([$association->resolution])
            ->concat($association->resolutionsHistory)
            ->filter()
            ->unique('id')
            ->filter(function (Resolution $resolution) use ($periodStart, $periodEnd) {
                if (! $resolution->isPending()) {
                    return false;
                }

                $resolutionDate = $resolution->date_start
                    ?? $resolution->date_document
                    ?? $resolution->created_at;

                return $resolutionDate
                    && Carbon::parse($resolutionDate)->betweenIncluded($periodStart, $periodEnd);
            })
            ->sortByDesc(fn (Resolution $r) => [$r->date_start?->timestamp ?? 0, $r->id])
            ->first();

        return $latest;
    }
}
