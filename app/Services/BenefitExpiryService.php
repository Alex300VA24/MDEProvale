<?php

namespace App\Services;

use App\Models\BeneficiaryHistory;
use App\Models\Notification;
use App\Models\State;
use App\Models\SystemAuditEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BenefitExpiryService
{
    public function process(?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $inactiveId = State::idFor(State::INACTIVE);
        if (! $inactiveId) {
            return 0;
        }

        $processed = 0;
        BeneficiaryHistory::query()
            ->whereNotNull('date_end')
            ->whereDate('date_end', '<', $today)
            ->where('state_id', '!=', $inactiveId)
            ->whereHas('typeBenefit', fn ($query) => $query->whereIn('abbreviation', ['GES', 'LAC']))
            ->with(['typeBenefit', 'beneficiary.person', 'beneficiary.partner.association'])
            ->chunkById(100, function ($histories) use ($inactiveId, $today, &$processed) {
                foreach ($histories as $history) {
                    DB::transaction(function () use ($history, $inactiveId, $today, &$processed) {
                        $locked = BeneficiaryHistory::query()->lockForUpdate()->find($history->id);
                        if (! $locked || (int) $locked->state_id === $inactiveId || ! $locked->date_end || Carbon::parse($locked->date_end)->gte($today)) {
                            return;
                        }

                        $fromState = $locked->state_id;
                        $before = ['state_id' => $fromState, 'date_end' => $locked->date_end];
                        $locked->update(['state_id' => $inactiveId]);
                        $eventKey = "benefit-expired:{$locked->id}:{$locked->date_end}";

                        SystemAuditEvent::firstOrCreate(['event_key' => $eventKey], [
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

                        Notification::firstOrCreate(['dedupe_key' => $eventKey], [
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
