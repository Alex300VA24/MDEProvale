<?php

namespace App\Repositories\Contracts;

use App\Models\Resolution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Resoluciones de reconocimiento de comités.
 */
interface ResolutionRepositoryInterface
{
    /** Filtros: search (documento o comité), state_id, anio, mes. */
    public function paginateFiltered(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Filtros: search (documento), state_id, anio. Ordena por fecha del documento. */
    public function paginateByDocument(array $filters, int $perPage = 10): LengthAwarePaginator;

    /** Todas las resoluciones, la de documento más reciente primero. */
    public function allByDocumentDate(): Collection;

    /** Resoluciones vigentes sin comité asignado; con `$includeId` también esa resolución. */
    public function availableForCommittees(?int $currentStateId, string $today, ?int $includeId = null): Collection;

    /** Años distintos de inicio, del más reciente al más antiguo. */
    public function startYears(): SupportCollection;

    public function create(array $data): Resolution;

    public function update(Resolution $resolution, array $data): bool;

    public function delete(Resolution $resolution): bool;

    /** Carga estado y comités para el recurso del API. */
    public function loadRelations(Resolution $resolution): Resolution;

    /** Si algún comité o directiva depende de la resolución. */
    public function isReferenced(Resolution $resolution): bool;
}
