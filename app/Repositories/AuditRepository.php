<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\SystemAuditEvent;
use App\Repositories\Contracts\AuditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditRepository implements AuditRepositoryInterface
{
    public function recordEvent(array $values): SystemAuditEvent
    {
        return SystemAuditEvent::create($values);
    }

    public function paginateEvents(?string $source, ?string $search, int $perPage): LengthAwarePaginator
    {
        $query = SystemAuditEvent::with(['fromState:id,title,abbreviation', 'toState:id,title,abbreviation', 'executedBy:id,names,father_surname,mother_surname'])
            ->latest('occurred_at');

        if ($source) {
            $query->where('source', $source);
        }

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('event_type', 'like', "%{$search}%")
                    ->orWhere('reason_code', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function recordEventOnce(string $eventKey, array $values): SystemAuditEvent
    {
        return SystemAuditEvent::firstOrCreate(['event_key' => $eventKey], $values);
    }

    public function notifyOnce(string $dedupeKey, array $values): Notification
    {
        return Notification::firstOrCreate(['dedupe_key' => $dedupeKey], $values);
    }
}
