<?php

namespace App\Services;

use App\Models\SystemAuditEvent;
use App\Repositories\Contracts\AuditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Consulta de la auditoría de transiciones de estado del sistema.
 */
class SystemAuditService
{
    public function __construct(private AuditRepositoryInterface $audit)
    {
    }

    public function events(?string $source, ?string $search, int $perPage): LengthAwarePaginator
    {
        $search = $search !== null ? trim($search) : null;

        return $this->audit->paginateEvents($source ?: null, $search ?: null, $perPage)
            ->through(fn (SystemAuditEvent $event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'subject' => class_basename($event->subject_type).' #'.$event->subject_id,
                'source' => $event->source,
                'from_state' => $event->fromState?->abbreviation,
                'to_state' => $event->toState?->abbreviation,
                'reason_code' => $event->reason_code,
                'executed_by' => $event->executedBy
                    ? trim("{$event->executedBy->names} {$event->executedBy->father_surname} {$event->executedBy->mother_surname}")
                    : 'Sistema',
                'occurred_at' => $event->occurred_at?->format('Y-m-d H:i:s'),
                'metadata' => $event->metadata,
            ]);
    }
}
