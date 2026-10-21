<?php

namespace App\Services;

use App\Models\BeneficiaryHistory;
use App\Models\State;
use App\Repositories\Contracts\AuditRepositoryInterface;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BenefitExpiryService
{
    public function __construct(
        private BeneficiaryRepositoryInterface $beneficiaryRepo,
        private AuditRepositoryInterface $audit
    ) {
    }

    public function process(?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $inactiveId = State::idFor(State::INACTIVE);
        if (! $inactiveId) {
            return 0;
        }

        $processed = 0;
        $this->beneficiaryRepo->chunkExpiredBenefitHistories($inactiveId, $today->toDateString(), 100, function ($histories) use ($inactiveId, $today, &$processed) {
            foreach ($histories as $history) {
                DB::transaction(function () use ($history, $inactiveId, $today, &$processed) {
                    $locked = $this->beneficiaryRepo->lockHistory($history->id);
                    if (! $locked || (int) $locked->state_id === $inactiveId || ! $locked->date_end || Carbon::parse($locked->date_end)->gte($today)) {
                        return;
                    }

                    $fromState = $locked->state_id;
                    $before = ['state_id' => $fromState, 'date_end' => $locked->date_end];
                    $this->beneficiaryRepo->updateHistory($locked, ['state_id' => $inactiveId]);
                    $eventKey = "benefit-expired:{$locked->id}:{$locked->date_end}";

                    $this->audit->recordEventOnce($eventKey, [
                        'event_type' => 'BENEFIT_AUTO_DEACTIVATED',
                        'subject_type' => BeneficiaryHistory::class,
                        'subject_id' => $locked->id,
                        'source' => 'SYSTEM',
                        'from_state_id' => $fromState,
                        'to_state_id' => $inactiveId,
                        'reason_code' => 'BENEFIT_END_DATE_REACHED',
                        'occurred_at' => now(),
                        'before_data' => $before,
                        'after_data' => ['state_id' => $inactiveId, 'date_end' => $locked->date_end],
                    ]);

                    $this->audit->notifyOnce($eventKey, [
                        'type' => 'alerta_beneficiarios',
                        'title' => 'Beneficio finalizado automáticamente',
                        'description' => 'El beneficio alcanzó su fecha de término y fue marcado como inactivo.',
                        'status' => 'informativo',
                        'requested_at' => now(),
                        'is_seen' => false,
                        'metadata' => ['beneficiary_history_id' => $locked->id, 'fecha_fin' => $locked->date_end],
                    ]);
                    $processed++;
                });
            }
        });

        return $processed;
    }
}
