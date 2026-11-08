<?php

namespace App\Services;

use App\Models\Resolution;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\ResolutionRepositoryInterface;
use App\Services\Alertas\VencimientoAlertService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Reconocimientos: resoluciones que acreditan a los comités.
 */
class ResolutionService
{
    public function __construct(
        private ResolutionRepositoryInterface $resolutions,
        private AssociationRepositoryInterface $associations,
        private CatalogRepositoryInterface $catalogs,
        private ResolutionStateService $resolutionStates,
        private AssociationStateService $associationStates,
        private VencimientoAlertService $expiryAlerts
    ) {
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $this->resolutionStates->syncAll();

        return $this->resolutions->paginateFiltered($filters, $perPage);
    }


    public function options(): array
    {
        return [
            'states' => $this->catalogs->temporalStates(['id', 'title', 'abbreviation']),
            'years' => $this->resolutions->startYears(),
        ];
    }

    public function create(array $data): Resolution
    {
        $resolution = $this->resolutions->create($data);
        $this->resolutionStates->sync($resolution);

        return $this->resolutions->loadRelations($resolution);
    }

    public function update(Resolution $resolution, array $data): Resolution
    {
        $this->resolutions->update($resolution, $data);
        $this->resolutionStates->sync($resolution);

        $this->associations->linkedToResolution($resolution->id)
            ->each(fn ($association) => $this->associationStates->sync($association));

        // Si la renovación aleja la fecha de término, el aviso pendiente se soluciona.
        $this->expiryAlerts->resolveSolvedResolutionAlerts();

        return $this->resolutions->loadRelations($resolution);
    }

    /**
     * @throws \DomainException si hay comités o directivas que dependen de la resolución
     */
    public function delete(Resolution $resolution): void
    {
        DB::transaction(function () use ($resolution) {
            // Los comités cuya resolución vigente es esta se eliminan junto con ella,
            // salvo que tengan socios o pecosas: ese caso sigue bloqueado.
            $this->associations->linkedToResolution($resolution->id)
                ->where('resolution_id', $resolution->id)
                ->each(function ($association) {
                    if ($this->associations->hasPartners($association) || $this->associations->hasPecosas($association)) {
                        throw new \DomainException('No se puede eliminar la resolución: su comité tiene socios o pecosas asociadas');
                    }

                    $this->associations->deleteAssociation($association);
                });

            if ($this->resolutions->isReferenced($resolution)) {
                throw new \DomainException('No se puede eliminar la resolución: tiene comités o directivas asociadas');
            }

            $this->resolutions->delete($resolution);
        });

        $this->expiryAlerts->resolveSolvedResolutionAlerts();
    }
}
