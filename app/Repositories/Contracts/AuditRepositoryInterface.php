<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\SystemAuditEvent;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eventos de auditoría y notificaciones del sistema, idempotentes por clave.
 */
interface AuditRepositoryInterface
{
    public function recordEvent(array $values): SystemAuditEvent;

    /** Eventos con estados y ejecutor, más recientes primero; filtra por origen y texto libre. */
    public function paginateEvents(?string $source, ?string $search, int $perPage): LengthAwarePaginator;

    /** Registra el evento solo si no existe uno con esa `event_key`. */
    public function recordEventOnce(string $eventKey, array $values): SystemAuditEvent;

    /** Crea la notificación solo si no existe una con esa `dedupe_key`. */
    public function notifyOnce(string $dedupeKey, array $values): Notification;
}
