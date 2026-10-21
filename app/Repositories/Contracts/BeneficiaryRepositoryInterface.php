<?php

namespace App\Repositories\Contracts;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BeneficiaryRepositoryInterface
{
    /** Filtros: search (identidad de la persona), partner_id, relationship_id. */
    public function paginateFiltered(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Beneficiarios activos en el rango con los historiales que lo cruzan. Filtros: search, partner_id, relationship_id. */
    public function paginateForPeriod(array $filters, string $startDate, string $endDate, int $perPage = 10): LengthAwarePaginator;

    /** Recorre en bloques los historiales GES/LAC con fin anterior a `$today` que no están inactivos. */
    public function chunkExpiredBenefitHistories(int $inactiveStateId, string $today, int $size, callable $callback): void;

    /** Historial con bloqueo de escritura; solo dentro de una transacción. */
    public function lockHistory(int $id): ?BeneficiaryHistory;

    /** Fecha de inicio más reciente entre todos los historiales. */
    public function latestHistoryBegin(): ?string;

    /** Carga las relaciones que exponen los recursos del API. */
    public function loadDetail(Beneficiarie $beneficiary): Beneficiarie;

    public function findByPartnerAndPerson(Partner $partner, mixed $personId): ?Beneficiarie;

    /** Socio (`partner`) o beneficiario al que se adjunta un documento. */
    public function findAttachable(string $type, int $id): Model;

    /** Historiales vigentes en la fecha. */
    public function activeHistories(Beneficiarie $beneficiary, string $date): Collection;

    public function hasHistories(Beneficiarie $beneficiary): bool;

    public function updateHistory(BeneficiaryHistory $history, array $data): bool;

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
