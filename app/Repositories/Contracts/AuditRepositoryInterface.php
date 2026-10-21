<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\SystemAuditEvent;

/**
 * Eventos de auditoría y notificaciones del sistema, idempotentes por clave.
 */
interface AuditRepositoryInterface
{
    /** Registra el evento solo si no existe uno con esa `event_key`. */
    public function recordEventOnce(string $eventKey, array $values): SystemAuditEvent;

    /** Crea la notificación solo si no existe una con esa `dedupe_key`. */
    public function notifyOnce(string $dedupeKey, array $values): Notification;
}
