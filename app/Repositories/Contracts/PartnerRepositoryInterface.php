<?php

namespace App\Repositories\Contracts;

use App\Models\Partner;
use App\Models\People;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartnerRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function findActiveByAssociation(int $associationId, string $startDate, string $endDate): Collection;
    public function countBeneficiariesForAssociationAtDate(int $associationId, string $date): int;

    /** Listado del módulo de socios con beneficiarios e historiales. */
    public function paginateForIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Listado del API de socios con beneficiarios, historiales y documentos. */
    public function paginateForApi(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Carga las relaciones que exponen los recursos del API. */
    public function loadDetail(Partner $partner): Partner;

    public function createPartner(array $data): Partner;

    public function updatePartner(Partner $partner, array $data): bool;

    public function createPerson(array $data): People;

    public function deleteBeneficiaries(Partner $partner): void;

    public function deletePartner(Partner $partner): void;

    public function loadForFicha(Partner $partner): Partner;

    /** Socios con nombre y DNI de su persona que coinciden con todas las palabras. */
    public function searchByTerms(array $terms, int $limit): Collection;
}
