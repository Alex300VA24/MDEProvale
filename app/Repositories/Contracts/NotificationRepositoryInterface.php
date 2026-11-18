<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Bandeja de notificaciones y solicitudes de recuperación de contraseña.
 */
interface NotificationRepositoryInterface
{
    /** Notificaciones visibles para el usuario, más recientes primero. */
    public function visibleTo(User $user, ?string $type, int $limit, ?string $channel = null): Collection;

    /** La misma bandeja, paginada. */
    public function paginateVisibleTo(User $user, ?string $type, int $perPage, ?string $channel = null): LengthAwarePaginator;

    public function unreadCountFor(User $user, ?string $channel = null): int;

    /** Marca como vistas las notificaciones visibles para el usuario. */
    public function markSeenFor(User $user, ?string $channel = null): void;

    public function hasPendingPasswordReset(User $user): bool;

    public function createPasswordReset(User $user): Notification;

    public function create(array $data): Notification;

    /** Avisos del tipo que siguen por solucionar. */
    public function openOfType(string $type): Collection;

    /** Pasa el aviso a solucionado y deja registrado el motivo. */
    public function markSolved(Notification $notification, string $reason, ?User $by = null): void;

    /** Indica si ya existe un aviso del tipo con esa clave de deduplicación. */
    public function existsWithDedupeKey(string $type, string $dedupeKey): bool;

    /** Aviso más reciente del tipo cuya clave de deduplicación empieza con el prefijo. */
    public function latestWithDedupePrefix(string $type, string $prefix): ?Notification;

    /** Elimina los avisos del tipo con esa clave de deduplicación; devuelve cuántos. */
    public function deleteByDedupeKey(string $type, string $dedupeKey): int;

    /** Cantidad de avisos sin ver que el usuario solicitó. */
    public function unreadRequestedBy(User $user): int;

    /** Marca la solicitud como aprobada/rechazada y vista. */
    public function process(Notification $notification, string $status, User $processedBy): Notification;
}
