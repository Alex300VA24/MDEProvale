<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function visibleTo(User $user, ?string $type, int $limit, ?string $channel = null): Collection
    {
        return $this->visibleQuery($user, $type, $channel)->limit($limit)->get();
    }

    public function paginateVisibleTo(User $user, ?string $type, int $perPage, ?string $channel = null): LengthAwarePaginator
    {
        return $this->visibleQuery($user, $type, $channel)->paginate($perPage);
    }

    /** Consulta base de la bandeja: lo visible para el usuario, más reciente primero. */
    private function visibleQuery(User $user, ?string $type, ?string $channel): Builder
    {
        $query = Notification::forChannel(
            $this->scopeToUser(Notification::with(['requestedByUser', 'processedByUser']), $user),
            $channel
        )->orderBy('requested_at', 'desc')->orderBy('id', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return $query;
    }

    public function unreadCountFor(User $user, ?string $channel = null): int
    {
        return Notification::unreadCountForUser($user, $channel);
    }

    public function markSeenFor(User $user, ?string $channel = null): void
    {
        Notification::forChannel($this->scopeToUser(Notification::where('is_seen', false), $user), $channel)
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

    public function deleteByDedupeKey(string $type, string $dedupeKey): int
    {
        return Notification::where('type', $type)
            ->where(fn ($query) => $query
                ->where('dedupe_key', $dedupeKey)
                ->orWhere('metadata->dedupe_key', $dedupeKey))
            ->delete();
    }

    public function openOfType(string $type): Collection
    {
        return Notification::where('type', $type)
            ->where('status', Notification::STATUS_OPEN)
            ->get();
    }

    public function markSolved(Notification $notification, string $reason, ?User $by = null): void
    {
        $notification->update([
            'status' => Notification::STATUS_SOLVED,
            'processed_at' => now(),
            'processed_by' => $by?->id,
            'is_seen' => true,
            'metadata' => ($notification->metadata ?? []) + ['solucion' => $reason],
        ]);
    }

    public function existsWithDedupeKey(string $type, string $dedupeKey): bool
    {
        return Notification::where('type', $type)
            ->where(fn ($query) => $query
                ->where('dedupe_key', $dedupeKey)
                ->orWhere('metadata->dedupe_key', $dedupeKey))
            ->exists();
    }

    public function latestWithDedupePrefix(string $type, string $prefix): ?Notification
    {
        return Notification::where('type', $type)
            ->where('dedupe_key', 'like', addcslashes($prefix, '%_\\') . '%')
            ->orderByDesc('id')
            ->first();
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
