<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\SystemAuditEvent;
use App\Repositories\Contracts\AuditRepositoryInterface;

class AuditRepository implements AuditRepositoryInterface
{
    public function recordEvent(array $values): SystemAuditEvent
    {
        return SystemAuditEvent::create($values);
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
