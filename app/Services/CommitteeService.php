<?php

namespace App\Services;

use App\Models\Association;
use App\Models\DocumentAttachment;
use App\Models\State;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\AuditRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\DirectiveRepositoryInterface;
use App\Repositories\Contracts\ResolutionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Comités (Club de Madres): consulta, alta, cambio de estado y directiva.
 */
class CommitteeService
{
    public function __construct(
        private AssociationRepositoryInterface $associations,
        private DirectiveRepositoryInterface $directives,
        private ResolutionRepositoryInterface $resolutions,
        private CatalogRepositoryInterface $catalogs,
        private AuditRepositoryInterface $audit,
        private AssociationStateService $states,
        private ResolutionStateService $resolutionStates,
        private PresidentAccountService $presidentAccounts,
        private DocumentAttachmentService $attachments
    ) {
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $this->states->syncAll();
        $associations = $this->associations->paginateClubs($filters, $perPage);

        Association::hydratePresidents($associations);
        $this->hydrateResolutions($associations);

        return $associations;
    }

    public function options(?int $includeResolutionId): array
    {
        $this->resolutionStates->syncAll();

        $resolutions = $this->resolutions->availableForCommittees(
            State::idFor(State::CURRENT),
            now()->toDateString(),
            $includeResolutionId
        );

        $existingCodes = $this->associations->idsAndCodes();
        $maxNumeric = 0;
        foreach ($existingCodes as $association) {
            $trimmed = trim((string) $association->code);
            if (is_numeric($trimmed)) {
                $maxNumeric = max($maxNumeric, (int) $trimmed);
            }
        }

        return [
            'states' => $this->catalogs->associationStates(),
            'place_sectors' => $this->catalogs->placeSectors(['*']),
            'type_premises' => $this->catalogs->typePremises(),
            'resolutions' => $resolutions,
            'next_code' => (string) ($maxNumeric + 1),
            'existing_codes' => $existingCodes,
        ];
    }

    public function show(Association $association): Association
    {
        $this->states->sync($association);
        $this->associations->loadClubRelations($association);
        $this->associations->loadPartnersWithPeople($association);

        $this->setPresident($association);
        $this->setCurrentPositions($association);
        $this->hydrateResolutions(collect([$association]));

        return $association;
    }

    public function create(array $data): Association
    {
        $data['state_id'] = State::idFor(State::CURRENT);

        $association = DB::transaction(function () use ($data) {
            $association = $this->associations->createAssociation($data);
            $this->states->sync($association);

            return $association;
        });

        return $this->present($association);
    }

    public function update(Association $association, array $data): Association
    {
        $this->associations->updateAssociation($association, $data);
        $this->states->sync($this->associations->fresh($association, 'resolution'));
        Association::clearPresidentaCache($association->id);

        return $this->present($association);
    }

    /**
     * Cambio manual de estado con auditoría y, opcionalmente, el acta adjunta.
     *
     * @throws ValidationException si falta el acta de renuncia en una baja por renuncia
     */
    public function changeStatus(Association $association, array $data, ?UploadedFile $acta, ?int $userId): Association
    {
        $target = $this->catalogs->findStateOrFail($data['state_id']);
        if ($data['reason'] === 'RENUNCIA' && $target->abbreviation === State::INACTIVE && ! $acta) {
            throw ValidationException::withMessages(['acta' => 'El acta de renuncia en PDF es obligatoria para inactivar el comité por renuncia.']);
        }

        DB::transaction(function () use ($association, $data, $acta, $userId) {
            $fromState = $association->state_id;
            $change = $this->associations->createStatusChange([
                'association_id' => $association->id,
                'from_state_id' => $fromState,
                'to_state_id' => $data['state_id'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'effective_date' => $data['effective_date'],
                'user_id' => $userId,
            ]);

            $this->associations->updateAssociation($association, ['state_id' => $data['state_id'], 'state_source' => 'MANUAL']);

            if ($acta) {
                $this->attachments->store($change, DocumentAttachment::TYPE_ACTA_RENUNCIA, $acta);
            }

            $this->audit->recordEvent([
                'event_key' => 'association-status-change:'.$change->id,
                'event_type' => 'ASSOCIATION_STATUS_CHANGED',
                'subject_type' => Association::class,
                'subject_id' => $association->id,
                'source' => 'USER',
                'from_state_id' => $fromState,
                'to_state_id' => $data['state_id'],
                'reason_code' => $data['reason'],
                'executed_by' => $userId,
                'occurred_at' => now(),
                'metadata' => ['status_change_id' => $change->id, 'effective_date' => $data['effective_date']],
            ]);
        });

        return $this->present($association);
    }

    public function restoreAutomaticStatus(Association $association): Association
    {
        $this->associations->updateAssociation($association, ['state_source' => 'AUTOMATIC']);
        $this->states->sync($this->associations->fresh($association, ['resolution', 'resolutionsHistory']));

        return $this->associations->loadClubRelations($this->associations->fresh($association));
    }

    /**
     * @throws \DomainException si el comité tiene socios, pecosas o historial de resoluciones
     */
    public function delete(Association $association): void
    {
        if (
            $this->associations->hasPartners($association)
            || $this->associations->hasPecosas($association)
            || $this->associations->hasResolutionHistory($association)
        ) {
            throw new \DomainException('No se puede eliminar: el comité tiene socios, pecosas o historial de resoluciones asociado');
        }

        $this->associations->deleteAssociation($association);
    }

    /**
     * Asigna un cargo de la directiva a una socia del comité.
     *
     * @throws \DomainException si la socia no pertenece al comité o no hay resolución asociada
     */
    public function assignPosition(Association $association, int $partnerId, string $positionTitle): Association
    {
        if (! $this->associations->partnerBelongs($association, $partnerId)) {
            throw new \DomainException('La socia seleccionada no pertenece a este comité');
        }

        DB::transaction(function () use ($association, $partnerId, $positionTitle) {
            $position = $this->directives->firstOrCreatePosition($positionTitle);
            $current = $this->catalogs->stateByAbbreviationOrFail(State::CURRENT);
            $expired = $this->catalogs->stateByAbbreviationOrFail(State::EXPIRED);

            $resolutionIds = $this->directives->committeeResolutionIds($association);
            $partnerIds = $this->associations->partnerIds($association);
            $this->directives->expireCurrent($partnerIds, $resolutionIds, $position->id, $current->id, $expired->id, now()->subDay()->toDateString());

            $resolutionId = $association->resolution_id ?? $resolutionIds->first();
            if (! $resolutionId) {
                throw new \DomainException('No hay resolución asociada al comité');
            }

            $this->directives->create([
                'resolution_id' => $resolutionId,
                'partner_id' => $partnerId,
                'position_id' => $position->id,
                'state_id' => $current->id,
                'date_start' => now()->toDateString(),
            ]);

            $this->syncPresidentAccount($association, $partnerId, $positionTitle);
        });

        $this->associations->loadClubRelations($association);
        $this->setPresident($association);
        $this->setCurrentPositions($association);
        $this->hydrateResolutions(collect([$association]));

        return $association;
    }

    /**
     * La vigencia del comité depende únicamente de su resolución.
     *
     * @throws \DomainException si la socia no pertenece al comité o no hay resolución asociada
     */
    public function assignPresident(Association $association, int $partnerId): Association
    {
        return $this->assignPosition($association, $partnerId, 'PRESIDENTA');
    }

    /**
     * Guarda varios cargos de la directiva en una sola operación.
     *
     * @param  \Illuminate\Support\Collection  $assignments  filas con `partner_id` y `position`
     * @throws \DomainException si alguna socia no pertenece al comité o no hay resolución asociada
     */
    public function assignPositions(Association $association, \Illuminate\Support\Collection $assignments): Association
    {
        $partnerIds = $assignments->pluck('partner_id')->map(fn ($id) => (int) $id)->unique();

        if ($this->associations->partnerIdsAmong($association, $partnerIds)->count() !== $partnerIds->count()) {
            throw new \DomainException('Una de las socias seleccionadas no pertenece a este comité');
        }

        DB::transaction(function () use ($association, $assignments) {
            $current = $this->catalogs->stateByAbbreviationOrFail(State::CURRENT);
            $expired = $this->catalogs->stateByAbbreviationOrFail(State::EXPIRED);

            $resolutionIds = $this->directives->committeeResolutionIds($association);
            $resolutionId = $association->resolution_id ?? $resolutionIds->first();
            if (! $resolutionId) {
                throw new \DomainException('No hay resolución asociada al comité');
            }

            $committeePartnerIds = $this->associations->partnerIds($association);
            $positions = $this->directives->positionsByTitles($assignments->pluck('position'));

            foreach ($assignments as $assignment) {
                $positionTitle = $assignment['position'];
                $partnerId = (int) $assignment['partner_id'];
                $position = $positions->get($positionTitle);

                $this->directives->expireCurrent($committeePartnerIds, $resolutionIds, $position->id, $current->id, $expired->id, now()->subDay()->toDateString());

                $this->directives->create([
                    'resolution_id' => $resolutionId,
                    'partner_id' => $partnerId,
                    'position_id' => $position->id,
                    'state_id' => $current->id,
                    'date_start' => now()->toDateString(),
                ]);

                $this->syncPresidentAccount($association, $partnerId, $positionTitle);
            }
        });

        $this->associations->loadClubRelations($association);
        $this->associations->loadPartnersWithPeople($association);
        $this->setPresident($association);
        $this->setCurrentPositions($association);
        $this->hydrateResolutions(collect([$association]));

        return $association;
    }

    /** Resolución vigente e historial ordenados por inicio, para el recurso. */
    public function hydrateResolutions($associations): void
    {
        foreach ($associations as $association) {
            $all = collect();

            if ($association->resolution) {
                $all->push($association->resolution);
            }

            foreach ($association->resolutionsHistory as $resolution) {
                if ($resolution->id !== $association->resolution_id) {
                    $all->push($resolution);
                }
            }

            $all = $all->sortByDesc('date_start')->values();

            $association->allResolutions = $all->all();
            $association->latestResolution = $all->first();
        }
    }

    private function syncPresidentAccount(Association $association, int $partnerId, string $positionTitle): void
    {
        if ($positionTitle !== 'PRESIDENTA') {
            return;
        }

        $partner = $this->catalogs->partnerWithPeople($partnerId);
        if ($partner) {
            $this->presidentAccounts->sync($partner);
        }
        Association::clearPresidentaCache($association->id);
    }

    private function present(Association $association): Association
    {
        $this->associations->loadClubRelations($association);
        $this->setPresident($association);
        $this->hydrateResolutions(collect([$association]));

        return $association;
    }

    private function setPresident(Association $association): void
    {
        $presidenta = $association->getPresidenta();
        $association->president_partner_id = $presidenta?->id;
        $association->president_name = $presidenta && $presidenta->people
            ? trim($presidenta->people->names.' '.$presidenta->people->father_lastname)
            : null;
    }

    private function setCurrentPositions(Association $association): void
    {
        $partnerIds = $this->associations->partnerIds($association);
        $currentStateId = State::idFor(State::CURRENT);

        $association->current_positions = $partnerIds->isEmpty() || ! $currentStateId
            ? []
            : $this->directives->currentCommitteePositions($partnerIds, $currentStateId);
    }
}
