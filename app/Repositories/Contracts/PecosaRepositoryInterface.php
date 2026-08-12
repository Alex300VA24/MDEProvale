<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Pecosa;

interface PecosaRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function findByAssociationAndPeriod(int $associationId, string $startDate, string $endDate): ?Pecosa;
    public function getPresidentDirectivesByAssociation(\Illuminate\Support\Collection $associationIds, int $presidentPositionId, int $activeStateId): \Illuminate\Support\Collection;
}