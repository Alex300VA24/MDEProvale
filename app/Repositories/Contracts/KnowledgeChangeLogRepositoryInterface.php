<?php

namespace App\Repositories\Contracts;

use App\Models\KnowledgeChangeLog;
use Illuminate\Pagination\LengthAwarePaginator;

/** Historial de trazabilidad de cambios detectados o extraídos del portal. */
interface KnowledgeChangeLogRepositoryInterface
{
    public function create(array $data): KnowledgeChangeLog;

    public function paginate(?string $source, ?string $search, int $perPage): LengthAwarePaginator;
}
