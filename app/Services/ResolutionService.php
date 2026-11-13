<?php

namespace App\Services;

use App\Models\Resolution;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
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
        private VencimientoAlertService $expiryAlerts,
        private MunicipalResolutionService $municipal,
        private NotificationRepositoryInterface $notifications
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
            // Resoluciones: Vigente, Vencido y Pendiente.
            'states' => $this->catalogs->resolutionStates(['id', 'title', 'abbreviation']),
            'years' => $this->resolutions->startYears(),
        ];
    }

    /**
     * Con $data['pending'] = true la resolución nace en estado Pendiente (exige
     * 'observation') y emite una notificación de tipo Regularización.
     */
    public function create(array $data): Resolution
    {
        $pending = (bool) ($data['pending'] ?? false);
        unset($data['pending']);

        if ($pending) {
            if (trim((string) ($data['observation'] ?? '')) === '') {
                throw new \DomainException('La observación es obligatoria para registrar una resolución como Pendiente.');
            }

            $data['state_id'] = State::idFor(State::PENDING)
                ?? throw new \DomainException('No existe el estado Pendiente en el catálogo.');
        } else {
            $data['observation'] = null;
        }

        if (! $pending) {
            $this->assertDocumentAvailable((string) ($data['document'] ?? ''));
        }

        $resolution = $this->resolutions->create($data);

        if ($pending) {
            $this->notifyRegularization($resolution);
        } else {
            $this->resolutionStates->sync($resolution);
        }

        return $this->resolutions->loadRelations($resolution);
    }

    /**
     * Pendiente -> Vigente/Vencido (según fecha de término). Exige el número de
     * resolución correcto y que exista en el portal municipal; si el portal no
     * responde, la excepción sube y el estado no cambia.
     *
     * @throws \DomainException si no está pendiente o el portal no la publica
     * @throws \App\Exceptions\MunicipalPortalException si el portal falla
     */
    public function regularize(Resolution $resolution, string $document, array $dates = []): Resolution
    {
        if (! $resolution->isPending()) {
            throw new \DomainException('Solo se pueden regularizar resoluciones en estado Pendiente.');
        }

        $document = trim($document);
        if ($document === '') {
            throw new \DomainException('Ingrese el número de resolución.');
        }

        // Consulta contra una copia: el portal se valida con el número ingresado, sin persistirlo aún.
        $candidate = $resolution->replicate();
        $candidate->id = $resolution->id;
        $candidate->document = $document;

        if (! $this->municipal->resolve($candidate)) {
            throw new \DomainException('La resolución '.$document.' no existe en el portal de la Municipalidad de La Esperanza.');
        }

        $this->assertDocumentAvailable($document, $resolution->id);

        DB::transaction(function () use ($resolution, $document, $dates) {
            $resolution->forceFill([
                ...array_intersect_key($dates, array_flip(['date_start', 'date_end'])),
                'document' => $document,
                'observation' => null,
                'state_id' => State::idFor(State::CURRENT),
            ])->save();
            $this->resolutionStates->sync($resolution);

            $this->solveRegularizationNotifications($resolution);
        });

        $this->associations->linkedToResolution($resolution->id)
            ->each(fn ($association) => $this->associationStates->sync($association));

        return $this->resolutions->loadRelations($resolution->refresh());
    }

    private function notifyRegularization(Resolution $resolution): void
    {
        $this->notifications->create([
            'type' => Notification::TYPE_REGULARIZATION,
            'dedupe_key' => 'regularizacion_'.$resolution->id,
            'title' => 'Resolución pendiente de regularización',
            'description' => 'La resolución '.$resolution->document.' se registró como Pendiente. Motivo: '.$resolution->observation,
            'status' => Notification::STATUS_OPEN,
            'user_id' => auth()->id(),
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'metadata' => [
                'resolution_id' => $resolution->id,
                'document' => $resolution->document,
                'observation' => $resolution->observation,
            ],
        ]);
    }

    private function solveRegularizationNotifications(Resolution $resolution): void
    {
        $this->notifications->openOfType(Notification::TYPE_REGULARIZATION)
            ->filter(fn ($n) => (int) ($n->metadata['resolution_id'] ?? 0) === $resolution->id)
            ->each(fn ($n) => $this->notifications->markSolved($n, 'resolución regularizada'));
    }

    /**
     * Un número de resolución publicada no se registra dos veces.
     *
     * @throws \DomainException
     */
    private function assertDocumentAvailable(string $document, ?int $exceptId = null): void
    {
        $document = trim($document);

        if ($this->resolutions->documentExists($document, $exceptId)) {
            throw new \DomainException('La resolución '.$document.' ya está registrada.');
        }
    }

    public function update(Resolution $resolution, array $data): Resolution
    {
        if (isset($data['document']) && trim($data['document']) !== $resolution->document && ! $resolution->isPending()) {
            $this->assertDocumentAvailable($data['document'], $resolution->id);
        }

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

            // La notificación de regularización pierde sentido sin la resolución.
            $this->notifications->deleteByDedupeKey(
                Notification::TYPE_REGULARIZATION,
                'regularizacion_'.$resolution->id
            );
        });

        $this->expiryAlerts->resolveSolvedResolutionAlerts();
    }
}
