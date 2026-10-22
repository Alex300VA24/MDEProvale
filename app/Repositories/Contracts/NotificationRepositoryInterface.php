<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bandeja de notificaciones y solicitudes de recuperación de contraseña.
 */
interface NotificationRepositoryInterface
{
    /** Notificaciones visibles para el usuario, más recientes primero. */
    public function visibleTo(User $user, ?string $type, int $limit): Collection;

    public function unreadCountFor(User $user): int;

    /** Marca como vistas las notificaciones visibles para el usuario. */
    public function markSeenFor(User $user): void;

    public function hasPendingPasswordReset(User $user): bool;

    public function createPasswordReset(User $user): Notification;

    public function create(array $data): Notification;

    /** Cantidad de avisos sin ver que el usuario solicitó. */
    public function unreadRequestedBy(User $user): int;

    /** Marca la solicitud como aprobada/rechazada y vista. */
    public function process(Notification $notification, string $status, User $processedBy): Notification;
}
