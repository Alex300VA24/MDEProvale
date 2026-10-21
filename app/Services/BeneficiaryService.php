<?php

namespace App\Services;

use App\Models\Beneficiarie;
use App\Models\DocumentAttachment;
use App\Models\State;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class BeneficiaryService
{
    public function __construct(
        private BeneficiaryRepositoryInterface $beneficiaryRepo,
        private CatalogRepositoryInterface $catalogs,
        private BenefitPeriodCalculator $benefitPeriods,
        private DocumentAttachmentService $attachments
    ) {
    }

    public function indexData(array $filters): array
    {
        return [
            'beneficiaries' => $this->beneficiaryRepo->paginateFiltered($filters),
            'partners' => $this->catalogs->partnersWithPeople(),
            'relationships' => $this->catalogs->relationships(),
        ];
    }

    /**
     * Beneficiarios activos en el mes pedido; sin período usa el mes del historial más reciente.
     *
     * @return array{0: LengthAwarePaginator, 1: int, 2: int} paginador, año y mes
     */
    public function paginateByPeriod(array $filters, ?int $year, ?int $month, int $perPage = 10): array
    {
        [$year, $month] = $this->resolvePeriod($year, $month);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return [$this->beneficiaryRepo->paginateForPeriod($filters, $startDate, $endDate, $perPage), $year, $month];
    }

    /** Socios y parentescos para los filtros del API. */
    public function optionsData(): array
    {
        return [
            'partners' => $this->catalogs->partnersForSelect()
                ->map(fn ($partner) => ['id' => $partner->id, 'name' => $partner->name]),
            'relationships' => $this->catalogs->relationshipsByTitles(['Apoderado', 'Hijo', 'Nieto']),
        ];
    }

    public function detail(Beneficiarie $beneficiary): Beneficiarie
    {
        return $this->beneficiaryRepo->loadDetail($beneficiary);
    }

    public function create(array $data): Beneficiarie
    {
        return $this->beneficiaryRepo->createBeneficiary($data);
    }

    /** Registra el beneficiario vigente y su historial de beneficio si viene completo. */
    public function register(array $data): Beneficiarie
    {
        $data['history_state_id'] = State::idFor(State::CURRENT);

        $beneficiary = $this->beneficiaryRepo->createBeneficiary([
            'person_id' => $data['person_id'],
            'partner_id' => $data['partner_id'],
            'relationship_id' => $data['relationship_id'],
            'priority' => $data['priority'] ?? 1,
        ]);

        $this->syncHistory($beneficiary, $data);

        return $this->detail($beneficiary);
    }

    public function modify(Beneficiarie $beneficiary, array $data): Beneficiarie
    {
        $this->beneficiaryRepo->updateBeneficiary($beneficiary, [
            'person_id' => $data['person_id'],
            'partner_id' => $data['partner_id'],
            'relationship_id' => $data['relationship_id'],
            'priority' => $data['priority'] ?? $beneficiary->priority ?? 1,
        ]);

        $this->syncHistory($beneficiary, $data);

        return $this->detail($beneficiary);
    }

    public function update(Beneficiarie $beneficiary, array $data): bool
    {
        return $this->beneficiaryRepo->updateBeneficiary($beneficiary, $data);
    }

    public function delete(Beneficiarie $beneficiary): void
    {
        $this->beneficiaryRepo->deleteBeneficiary($beneficiary);
    }

    /**
     * Da de baja al beneficiario: sin historial se elimina; con historial vigente
     * se cierran los períodos al día de hoy.
     *
     * @throws \DomainException si solo tiene períodos históricos (ninguno vigente)
     */
    public function deactivate(Beneficiarie $beneficiary): void
    {
        $today = now()->toDateString();
        $active = $this->beneficiaryRepo->activeHistories($beneficiary, $today);

        if ($active->isEmpty()) {
            if ($this->beneficiaryRepo->hasHistories($beneficiary)) {
                throw new \DomainException('No se puede alterar un período histórico. Seleccione el período vigente para dar de baja al beneficiario.');
            }

            $this->beneficiaryRepo->deleteBeneficiary($beneficiary);

            return;
        }

        $expiredStateId = State::idFor(State::EXPIRED);
        foreach ($active as $history) {
            $this->beneficiaryRepo->updateHistory($history, array_filter([
                'date_end' => $today,
                'state_id' => $expiredStateId,
            ], fn ($value) => $value !== null));
        }
    }

    /**
     * Adjunta un documento al socio o beneficiario indicado.
     *
     * @param string $type `partner` o `beneficiarie`
     */
    public function attachDocument(string $type, int $id, string $documentType, UploadedFile $file): DocumentAttachment
    {
        $attachable = $this->beneficiaryRepo->findAttachable($type, $id);

        return $this->attachments->store($attachable, $documentType, $file);
    }

    /** @return array{0: int, 1: int} año y mes */
    private function resolvePeriod(?int $year, ?int $month): array
    {
        if ($year && $month) {
            return [max(2019, min(now()->year + 1, $year)), max(1, min(12, $month))];
        }

        $latest = $this->beneficiaryRepo->latestHistoryBegin();
        $date = $latest ? Carbon::parse($latest) : now();

        return [$date->year, $date->month];
    }

    /**
     * Crea o actualiza el historial de beneficio de la fecha de inicio. Requiere tipo
     * de beneficio, estado y fecha de inicio (misma regla que PartnerService al guardar
     * beneficiarios anidados).
     */
    private function syncHistory(Beneficiarie $beneficiary, array $data): void
    {
        if (empty($data['type_benefit_id']) || empty($data['history_state_id']) || empty($data['date_begin'])) {
            return;
        }

        $abbreviation = $this->beneficiaryRepo->typeBenefitAbbreviation($data['type_benefit_id']);
        $birthdate = $this->beneficiaryRepo->personBirthdate($beneficiary->person_id);

        $this->beneficiaryRepo->upsertHistory($beneficiary, $data['date_begin'], [
            'weight' => 0,
            'height' => 0,
            'hmg' => 0,
            'date_end' => in_array($abbreviation, ['NI0', 'NI7'], true)
                ? $this->benefitPeriods->fromBirthdate($birthdate, $abbreviation)
                : null,
            'type_benefit_id' => $data['type_benefit_id'],
            'state_id' => $data['history_state_id'],
            'reason_disqualification_id' => $data['reason_disqualification_id'] ?? null,
            'is_malnourished' => false,
            'is_disabled' => false,
        ]);
    }
}
