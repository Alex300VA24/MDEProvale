<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function visibleTo(User $user, ?string $type, int $limit): Collection
    {
        $query = $this->scopeToUser(Notification::with(['requestedByUser', 'processedByUser']), $user)
            ->orderBy('requested_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->limit($limit)->get();
    }

    public function unreadCountFor(User $user): int
    {
        return Notification::unreadCountForUser($user);
    }

    public function markSeenFor(User $user): void
    {
        $this->scopeToUser(Notification::where('is_seen', false), $user)
            ->update(['is_seen' => true, 'seen_at' => now()]);
    }

    public function hasPendingPasswordReset(User $user): bool
    {
        return Notification::where('type', 'password_reset')
            ->where('requested_by', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function createPasswordReset(User $user): Notification
    {
        return Notification::createPasswordResetRequest($user);
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function unreadRequestedBy(User $user): int
    {
        return Notification::where('requested_by', $user->id)->where('is_seen', false)->count();
    }

    public function process(Notification $notification, string $status, User $processedBy): Notification
    {
        $notification->update([
            'status' => $status,
            'processed_at' => now(),
            'processed_by' => $processedBy->id,
            'is_seen' => true,
            'seen_at' => now(),
        ]);

        return $notification->fresh(['requestedByUser', 'processedByUser']);
    }

    /** El administrador ve todo; el resto, sus solicitudes y los avisos generales. */
    private function scopeToUser(Builder $query, User $user): Builder
    {
        if (! $user->isAdmin()) {
            $query->where(fn ($q) => $q
                ->where('requested_by', $user->id)
                ->orWhereIn('type', Notification::SYSTEM_WIDE_TYPES));
        }

        return $query;
    }
}
