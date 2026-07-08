<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartnerRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function findActiveByAssociation(int $associationId, string $date): Collection;
    public function countBeneficiariesForAssociationAtDate(int $associationId, string $date): int;
}