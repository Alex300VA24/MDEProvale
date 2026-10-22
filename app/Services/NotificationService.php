<?php

namespace App\Services;

use App\Exceptions\SistemaException;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Notificaciones y solicitudes de recuperación de contraseña.
 */
class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private UserRepositoryInterface $users
    ) {
    }

    public function list(User $user, ?string $type, int $limit): Collection
    {
        return $this->notifications->visibleTo($user, $type, min(100, max(1, $limit)));
    }

    /** @return array{count:int, label:string} */
    public function unreadCount(User $user): array
    {
        $count = $this->notifications->unreadCountFor($user);

        return ['count' => $count, 'label' => $count > 9 ? '9+' : (string) $count];
    }

    public function markSeen(User $user): void
    {
        $this->notifications->markSeenFor($user);
    }

    /** Aprueba la solicitud y restablece la contraseña del solicitante a su DNI. */
    public function approve(Notification $notification, User $admin): Notification
    {
        $this->assertPending($notification);

        $requester = $this->users->find((int) $notification->requested_by);
        if (! $requester) {
            throw new SistemaException('No se encontró el usuario de la solicitud.', 404);
        }

        $this->users->update($requester, ['password' => Hash::make($requester->dni)]);

        return $this->notifications->process($notification, 'approved', $admin);
    }

    public function reject(Notification $notification, User $admin): Notification
    {
        $this->assertPending($notification);

        return $this->notifications->process($notification, 'rejected', $admin);
    }

    /**
     * Solicitud pública de recuperación desde el login.
     *
     * @return array{unreadCount:int, label:int|string}
     */
    public function requestPasswordReset(string $email): array
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new SistemaException('El correo electrónico no es válido.');
        }

        $user = $this->users->findByEmail($email);
        if (! $user) {
            throw new SistemaException('El correo electrónico no está registrado en el sistema.');
        }

        if ($this->notifications->hasPendingPasswordReset($user)) {
            throw new SistemaException('Ya tienes una solicitud de recuperación de contraseña pendiente. Espera a que un administrador la procese.');
        }

        $this->notifications->createPasswordReset($user);

        $unread = $this->notifications->unreadRequestedBy($user);

        return ['unreadCount' => $unread, 'label' => $unread > 9 ? '9+' : $unread];
    }

    /** Deja constancia de que el usuario pidió el enlace de restablecimiento. */
    public function recordResetLinkRequest(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (! $user) {
            return;
        }

        $this->notifications->create([
            'type' => 'password_reset',
            'title' => 'Solicitud de recuperación de contraseña',
            'description' => 'El usuario ' . $user->email . ' solicitó restablecer su contraseña.',
            'status' => 'pending',
            'user_id' => $user->id,
            'requested_by' => $user->id,
            'requested_at' => now(),
            'metadata' => [
                'email' => $user->email,
                'username' => $user->username,
            ],
        ]);
    }

    private function assertPending(Notification $notification): void
    {
        if ($notification->status !== 'pending') {
            throw new SistemaException('La solicitud ya fue procesada.');
        }
    }
}
