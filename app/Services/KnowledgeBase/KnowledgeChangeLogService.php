<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeChangeLog;
use App\Models\Notification;
use App\Repositories\Contracts\KnowledgeChangeLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Auditoría histórica de cambios detectados (portal, beneficiarios, resoluciones). */
class KnowledgeChangeLogService
{
    public function __construct(private KnowledgeChangeLogRepositoryInterface $logs)
    {
    }

    public function record(
        string $source,
        string $eventType,
        string $title,
        ?string $summary = null,
        array $details = [],
        ?Notification $notification = null,
        ?string $priority = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $triggeredBy = null,
    ): ?KnowledgeChangeLog {
        try {
            return $this->logs->create([
                'source' => $source,
                'event_type' => $eventType,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'title' => mb_substr($title, 0, 200),
                'summary' => $summary,
                'priority' => $priority,
                'details' => $details ?: null,
                'notification_id' => $notification?->id,
                'triggered_by' => $triggeredBy,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            // La trazabilidad nunca debe romper el flujo que la origina.
            Log::warning('No se pudo registrar el historial de cambios.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function paginate(?string $source, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->logs->paginate($source, $search, min(50, max(5, $perPage)));
    }
}
