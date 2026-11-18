<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Persistencia del Historial de Cambios (audit_logs y audit_log_changes).
 */
interface AuditTrailRepositoryInterface
{
    /** Guarda el evento y su detalle campo a campo en una sola transacción. */
    public function store(array $log, array $changes): AuditLog;

    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function changesOf(AuditLog $log): Collection;

    /** @return array{modules: Collection, users: Collection} */
    public function filterValues(): array;
}
