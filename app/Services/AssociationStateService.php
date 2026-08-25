<?php

namespace App\Services;

use App\Models\Association;
use App\Models\State;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AssociationStateService
{
    public function abbreviationFor(Association $association, ?CarbonInterface $today = null): string
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $latestResolution = collect([$association->resolution])
            ->concat($association->relationLoaded('resolutionsHistory') ? $association->resolutionsHistory : collect())
            ->filter()
            ->unique('id')
            ->sortByDesc('date_start')
            ->first();
        $dateEnd = $latestResolution?->date_end;

        if (!$dateEnd || $dateEnd->copy()->startOfDay()->greaterThanOrEqualTo($today)) {
            return State::CURRENT;
        }

        $pendingUntil = Carbon::parse($dateEnd)->addMonthNoOverflow()->startOfDay();

        return $today->lessThanOrEqualTo($pendingUntil)
            ? State::PENDING
            : State::EXPIRED;
    }

    public function sync(Association $association, ?CarbonInterface $today = null): bool
    {
        $association->loadMissing(['resolution', 'resolutionsHistory']);
        $stateId = State::idFor($this->abbreviationFor($association, $today));

        if (!$stateId || (int) $association->state_id === $stateId) {
            return false;
        }

        return $association->forceFill(['state_id' => $stateId])->saveQuietly();
    }

    public function syncAll(?CarbonInterface $today = null): int
    {
        $updated = 0;

        Association::with(['resolution', 'resolutionsHistory'])->chunkById(200, function ($associations) use (&$updated, $today) {
            foreach ($associations as $association) {
                $updated += (int) $this->sync($association, $today);
            }
        });

        return $updated;
    }
}
