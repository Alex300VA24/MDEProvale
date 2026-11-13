<?php

namespace App\Services;

use App\Models\DocumentAttachment;
use App\Models\Partner;
use App\Models\State;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerService
{
    public function __construct(
        private PartnerRepositoryInterface $partnerRepo,
        private BeneficiaryRepositoryInterface $beneficiaryRepo,
        private CatalogRepositoryInterface $catalogs,
        private PDFService $pdfService,
        private BenefitPeriodCalculator $benefitPeriods,
        private DocumentAttachmentService $attachments
    ) {
    }

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->partnerRepo->paginateForApi($filters, $perPage);
    }

    /** Catálogos de los formularios de socios del API. */
    public function optionsData(): array
    {
        return [
            'associations' => $this->catalogs->associationsWithAddress(),
            'states' => $this->catalogs->temporalStates(['id', 'title', 'abbreviation']),
            'people' => $this->catalogs->recentPeople(),
            'all_people' => $this->catalogs->peopleByName(),
            'relationships' => $this->catalogs->relationshipsByTitles(['Apoderado', 'Hijo', 'Nieto']),
            'place_sectors' => $this->catalogs->placeSectors(['*'], ['place:id,code,title', 'sector:id,title']),
            'type_benefits' => $this->catalogs->typeBenefitsByAbbreviations(['NI0', 'NI7', 'TBC', 'DIS']),
            'reason_disqualifications' => $this->catalogs->reasonDisqualifications(),
        ];
    }

    public function detail(Partner $partner): Partner
    {
        return $this->partnerRepo->loadDetail($partner);
    }

    /**
     * Registra el socio, sus beneficiarios y los documentos adjuntos en una sola transacción.
     *
     * @param array $documents family, conadis y beneficiaries (índice => archivo)
     */
    public function register(array $partnerData, ?array $beneficiaries, array $context, array $documents): Partner
    {
        return DB::transaction(function () use ($partnerData, $beneficiaries, $context, $documents) {
            $partner = $this->storeWithBeneficiaries($partnerData, $beneficiaries, $context);
            $this->storeRegistrationDocuments($partner, $beneficiaries ?? [], $documents);

            return $partner;
        });
    }

    public function modify(Partner $partner, array $partnerData, ?array $beneficiaries, array $context, array $documents): Partner
    {
        return DB::transaction(function () use ($partner, $partnerData, $beneficiaries, $context, $documents) {
            $partner = $this->updateWithBeneficiaries($partner, $partnerData, $beneficiaries, $context);
            $this->storeRegistrationDocuments($partner, $beneficiaries ?? [], $documents);

            return $partner;
        });
    }

    /** Datos del listado de socios con los catálogos de los formularios. */
    public function indexData(array $filters): array
    {
        return [
            'partners' => $this->partnerRepo->paginateForIndex($filters),
            'associations' => $this->catalogs->associations(['id', 'name']),
            'states' => $this->catalogs->temporalStates(['id', 'title']),
            'people' => $this->catalogs->recentPeople(),
            'allPeople' => $this->catalogs->peopleByName(),
            'relationships' => $this->catalogs->relationships(['id', 'title']),
            'placeSectors' => $this->catalogs->placeSectors(['*'], ['place:id,code,title', 'sector:id,title']),
            'typeBenefits' => $this->catalogs->typeBenefits(),
            'reasonDisqualifications' => $this->catalogs->reasonDisqualifications(),
        ];
    }

    public function allAssociations()
    {
        return $this->catalogs->associations(['*']);
    }

    public function partnerForFicha(Partner $partner): Partner
    {
        return $this->partnerRepo->loadForFicha($partner);
    }

    public function storeWithBeneficiaries(array $partnerData, ?array $beneficiaries = null, array $benefitContext = []): Partner
    {
        return DB::transaction(function () use ($partnerData, $beneficiaries, $benefitContext) {
            if (isset($partnerData['create_person']) && $partnerData['create_person']) {
                $person = $this->partnerRepo->createPerson($partnerData);
                $partnerData['person_id'] = $person->id;
            }

            $this->assertNoDuplicates((int) $partnerData['person_id'], $beneficiaries ?? []);

            $partner = $this->partnerRepo->createPartner($partnerData);
            $this->syncBeneficiaries($partner, $beneficiaries ?? []);
            $this->syncPartnerAsBeneficiary($partner, $benefitContext);

            return $partner;
        });
    }

    public function updateWithBeneficiaries(Partner $partner, array $partnerData, ?array $beneficiaries = null, array $benefitContext = []): Partner
    {
        if ($beneficiaries !== null) {
            $own = $partner->beneficiaries()->where('person_id', $partner->person_id)->pluck('id')->all();
            $others = array_filter($beneficiaries, fn ($b) => ! (isset($b['id']) && in_array((int) $b['id'], $own, true)));
            $this->assertNoDuplicates((int) ($partnerData['person_id'] ?? $partner->person_id), $others, $partner);
        }

        $this->partnerRepo->updatePartner($partner, $partnerData);

        if ($beneficiaries !== null) {
            $this->syncExistingBeneficiaries($partner, $beneficiaries);
        }

        $this->syncPartnerAsBeneficiary($partner, $benefitContext);

        return $partner;
    }

    /**
     * Reglas de unicidad de la ficha: una persona es socia vigente de un solo comité,
     * no puede ser su propia beneficiaria, y un mismo beneficiario no se repite en la
     * ficha ni figura vigente con otra socia.
     *
     * @throws ValidationException
     */
    private function assertNoDuplicates(int $personId, array $beneficiaries, ?Partner $partner = null): void
    {
        $today = now()->toDateString();

        if ((! $partner || $partner->person_id !== $personId)
            && $this->partnerRepo->personHasActivePartner($personId, $today, $partner?->id)) {
            throw ValidationException::withMessages([
                'person_id' => 'La persona ya es socia vigente de un comité.',
            ]);
        }

        $ids = collect($beneficiaries)->pluck('person_id')->filter()->map(fn ($id) => (int) $id);

        if ($ids->contains($personId)) {
            throw ValidationException::withMessages([
                'beneficiaries' => 'La socia no puede registrarse como su propia beneficiaria.',
            ]);
        }

        if ($ids->count() !== $ids->unique()->count()) {
            throw ValidationException::withMessages([
                'beneficiaries' => 'Un beneficiario está repetido en la ficha.',
            ]);
        }

        if ($ids->isNotEmpty() && $this->partnerRepo->beneficiariesTakenByOtherPartner($ids->all(), $today, $partner?->id)) {
            throw ValidationException::withMessages([
                'beneficiaries' => 'Un beneficiario ya está registrado como vigente con otra socia.',
            ]);
        }
    }

    public function deleteWithRelations(Partner $partner): void
    {
        if ($this->partnerRepo->hasPecosas($partner)) {
            throw ValidationException::withMessages([
                'partner' => 'No se puede eliminar: el socio figura como presidenta o gestora en PECOSAs registradas.',
            ]);
        }

        $documents = $this->partnerRepo->beneficiaryDocuments($partner)->concat($partner->documents);

        DB::transaction(function () use ($partner) {
            $this->partnerRepo->deleteBeneficiaries($partner);
            $this->partnerRepo->deletePartner($partner);
        });

        $documents->each(fn (DocumentAttachment $document) => $this->attachments->delete($document));
    }

    /**
     * Actualiza los beneficiarios que traen `id`, crea los nuevos y elimina los que ya no vienen.
     * La fila del propio socio (beneficiario de sí mismo) no se toca aquí.
     */
    private function syncExistingBeneficiaries(Partner $partner, array $beneficiaries): void
    {
        $existing = $partner->beneficiaries()
            ->where('person_id', '!=', $partner->person_id)
            ->get()
            ->keyBy('id');

        $kept = [];
        $created = [];

        foreach ($beneficiaries as $b) {
            $beneficiary = isset($b['id']) ? $existing->get((int) $b['id']) : null;
            if (! $beneficiary) {
                $created[] = $b;
                continue;
            }

            $kept[] = $beneficiary->id;
            $birthdate = $this->beneficiaryRepo->personBirthdate((int) $b['person_id']);
            $b['type_benefit_id'] = $this->typeBenefitForAge($b['type_benefit_id'] ?? null, $birthdate);
            $this->beneficiaryRepo->updateBeneficiary($beneficiary, [
                'person_id' => $b['person_id'],
                'relationship_id' => $b['relationship_id'],
                'priority' => $this->priorityForBirthdate($birthdate) ?? $b['priority'] ?? $beneficiary->priority,
            ]);

            $history = $beneficiary->histories()->latest('date_begin')->first();
            if ($history && ! empty($b['type_benefit_id'])) {
                $this->beneficiaryRepo->updateHistory($history, [
                    'type_benefit_id' => $b['type_benefit_id'],
                    'date_end' => $this->benefitEndDate($b['person_id'], $b['type_benefit_id']),
                ]);
            }
        }

        $removedIds = $existing->keys()->diff($kept)->values()->all();
        if ($removedIds) {
            $documents = $this->partnerRepo->beneficiaryDocuments($partner, $removedIds);
            $this->partnerRepo->deleteBeneficiaries($partner, $removedIds);
            $documents->each(fn (DocumentAttachment $document) => $this->attachments->delete($document));
        }

        $this->syncBeneficiaries($partner, $created);
    }

    private function storeRegistrationDocuments(Partner $partner, array $beneficiaries, array $documents): void
    {
        if (! empty($documents['family'])) {
            $this->attachments->store($partner, DocumentAttachment::TYPE_EXPEDIENTE_UNICO, $documents['family']);
        }

        if (! empty($documents['conadis'])) {
            $this->attachments->store($partner, DocumentAttachment::TYPE_CONADIS, $documents['conadis']);
        }

        foreach ($documents['beneficiaries'] ?? [] as $index => $file) {
            $beneficiary = $this->beneficiaryRepo->findByPartnerAndPerson($partner, $beneficiaries[$index]['person_id'] ?? null);
            if ($beneficiary && $file) {
                $this->attachments->store($beneficiary, DocumentAttachment::TYPE_EXPEDIENTE_UNICO, $file);
            }
        }
    }

    private function syncBeneficiaries(Partner $partner, array $beneficiaries): void
    {
        foreach ($beneficiaries as $b) {
            if (empty($b['person_id']) || empty($b['relationship_id'])) continue;

            $birthdate = $this->beneficiaryRepo->personBirthdate((int) $b['person_id']);
            $b['type_benefit_id'] = $this->typeBenefitForAge($b['type_benefit_id'] ?? null, $birthdate);

            $ben = $this->beneficiaryRepo->createBeneficiary([
                'person_id' => $b['person_id'],
                'partner_id' => $partner->id,
                'relationship_id' => $b['relationship_id'],
                'priority' => $this->priorityForBirthdate($birthdate) ?? $b['priority'] ?? 1,
            ]);

            if (!empty($b['type_benefit_id']) && !empty($b['history_state_id'])
                && !empty($b['date_begin'])) {
                $this->beneficiaryRepo->createHistory([
                    'weight' => 0,
                    'height' => 0,
                    'hmg' => 0,
                    'date_begin' => $b['date_begin'] ?? now()->toDateString(),
                    'date_end' => $this->benefitEndDate($b['person_id'], $b['type_benefit_id']),
                    'type_benefit_id' => $b['type_benefit_id'],
                    'beneficiary_id' => $ben->id,
                    'state_id' => $b['history_state_id'],
                    'reason_disqualification_id' => $b['reason_disqualification_id'] ?? null,
                    'is_malnourished' => false,
                    'is_disabled' => false,
                ]);
            }
        }
    }

    private function syncPartnerAsBeneficiary(Partner $partner, array $context = []): void
    {
        $condition = $partner->socioeconomic_condition;
        $abbreviation = match ($condition) {
            'GESTANTE' => 'GES',
            'LACTANTE' => 'LAC',
            'ADULTO_MAYOR' => 'ADU',
            'DISCAPACITADO' => 'DIS',
            'OTROS' => preg_match('/\b(tb|tbc|tuberc|tebec)/i', (string) $partner->observations) ? 'TBC' : null,
            default => null,
        };

        if (! $abbreviation) {
            return;
        }

        $relationship = $this->beneficiaryRepo->firstOrCreateRelationship('Socio');
        $typeBenefit = $this->beneficiaryRepo->findTypeBenefitByAbbreviation($abbreviation);
        if (! $typeBenefit) {
            return;
        }

        $isMother = in_array($abbreviation, ['GES', 'LAC'], true);
        $priority = $isMother ? 1 : 2;

        $beneficiary = $this->beneficiaryRepo->firstOrCreatePartnerBeneficiary($partner, [
            'relationship_id' => $relationship->id,
            'priority' => $priority,
        ]);

        $existingHistory = $this->beneficiaryRepo->latestHistory($beneficiary, $typeBenefit->id);
        $existingObstetric = $existingHistory?->obstetricData;

        $child = null;
        if ($isMother) {
            $child = $this->beneficiaryRepo->findChild(
                $partner,
                $beneficiary,
                ! empty($context['benefit_child_person_id']) ? (int) $context['benefit_child_person_id'] : null,
                $existingObstetric?->child_beneficiary_id
            );
        }

        $dateBegin = $partner->date_begin ?? now()->toDateString();
        $fum = $abbreviation === 'GES'
            ? (array_key_exists('fum', $context) ? $context['fum'] : $existingObstetric?->date_last_menstrual_period)
            : null;

        $history = $this->beneficiaryRepo->upsertHistory($beneficiary, $dateBegin, [
            'weight' => 0,
            'height' => 0,
            'hmg' => 0,
            'date_end' => $isMother
                ? $this->benefitPeriods->fromBirthdate($abbreviation === 'GES' ? ($fum ?: $dateBegin) : $dateBegin, $abbreviation)
                : $this->benefitEndDate($partner->person_id, $typeBenefit->id),
            'type_benefit_id' => $typeBenefit->id,
            'state_id' => $existingHistory?->state?->abbreviation === State::INACTIVE
                ? $existingHistory->state_id
                : ($partner->state_id ?: State::idFor(State::CURRENT)),
            'reason_disqualification_id' => null,
            'is_malnourished' => false,
            'is_disabled' => false,
        ]);

        if ($isMother) {
            $this->beneficiaryRepo->upsertObstetricData($history, [
                'date_last_menstrual_period' => $fum,
                'date_estimated_parturition' => ! empty($fum)
                    ? Carbon::parse($fum)->addDays(280)->toDateString()
                    : null,
                'date_parturition' => $child?->person?->birthdate,
                'date_end_breastfeeding' => $abbreviation === 'LAC'
                    ? Carbon::parse($dateBegin)->addYearsNoOverflow(2)->subDay()->toDateString()
                    : null,
                'child_beneficiary_id' => $child?->id,
            ]);
        }
    }

    /**
     * Prioridad según la edad del niño: 0 a 6 años (hasta cumplir 7) = 1ra; mayor de 6 = 2da.
     * Sin fecha de nacimiento devuelve null para respetar el valor enviado.
     */
    public function priorityForBirthdate(mixed $birthdate): ?int
    {
        if (! $birthdate) {
            return null;
        }

        return Carbon::parse($birthdate)->age <= 6 ? 1 : 2;
    }

    /** NI0 (0-6 años) o NI7 (mayor de 6) según la edad; otros tipos de beneficio no se tocan. */
    private function typeBenefitForAge(?int $typeBenefitId, mixed $birthdate): ?int
    {
        if (! $typeBenefitId || ! $birthdate) {
            return $typeBenefitId;
        }

        if (! in_array($this->beneficiaryRepo->typeBenefitAbbreviation($typeBenefitId), ['NI0', 'NI7'], true)) {
            return $typeBenefitId;
        }

        $abbreviation = Carbon::parse($birthdate)->age <= 6 ? 'NI0' : 'NI7';

        return $this->beneficiaryRepo->findTypeBenefitByAbbreviation($abbreviation)?->id ?? $typeBenefitId;
    }

    private function benefitEndDate(int $personId, int $typeBenefitId): ?string
    {
        $birthdate = $this->beneficiaryRepo->personBirthdate($personId);
        $abbreviation = $this->beneficiaryRepo->typeBenefitAbbreviation($typeBenefitId);

        return $this->benefitPeriods->fromBirthdate($birthdate, $abbreviation);
    }
}
