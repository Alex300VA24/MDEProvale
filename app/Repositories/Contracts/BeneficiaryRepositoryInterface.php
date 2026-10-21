<?php

namespace App\Repositories\Contracts;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use Illuminate\Pagination\LengthAwarePaginator;

interface BeneficiaryRepositoryInterface
{
    /** Filtros: search (identidad de la persona), partner_id, relationship_id. */
    public function paginateFiltered(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function createPerson(array $data): People;

    public function createBeneficiary(array $data): Beneficiarie;

    public function updateBeneficiary(Beneficiarie $beneficiary, array $data): bool;

    public function deleteBeneficiary(Beneficiarie $beneficiary): void;

    public function createHistory(array $data): BeneficiaryHistory;

    public function firstOrCreateRelationship(string $title): Relationship;

    public function findTypeBenefitByAbbreviation(string $abbreviation): ?TypeBenefit;

    public function personBirthdate(int $personId): mixed;

    public function typeBenefitAbbreviation(int $typeBenefitId): ?string;

    public function firstOrCreatePartnerBeneficiary(Partner $partner, array $values): Beneficiarie;

    public function latestHistory(Beneficiarie $beneficiary, int $typeBenefitId): ?BeneficiaryHistory;

    /** Hijo(a) vinculado(a) del socio, excluyendo al propio beneficiario. */
    public function findChild(Partner $partner, Beneficiarie $self, ?int $personId, ?int $childBeneficiaryId): ?Beneficiarie;

    public function upsertHistory(Beneficiarie $beneficiary, string $dateBegin, array $values): BeneficiaryHistory;

    public function upsertObstetricData(BeneficiaryHistory $history, array $values): void;
}
