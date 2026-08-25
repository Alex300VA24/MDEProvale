<?php

namespace App\Services;

use App\Models\Resolution;
use App\Models\State;
use Carbon\CarbonInterface;

class ResolutionStateService
{
    public function abbreviationFor(Resolution $resolution, ?CarbonInterface $today = null): string
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return $resolution->date_end && $resolution->date_end->copy()->startOfDay()->lt($today)
            ? State::EXPIRED
            : State::CURRENT;
    }

    public function sync(Resolution $resolution, ?CarbonInterface $today = null): bool
    {
        $stateId = State::idFor($this->abbreviationFor($resolution, $today));

        if (!$stateId || (int) $resolution->state_id === $stateId) {
            return false;
        }

        return $resolution->forceFill(['state_id' => $stateId])->saveQuietly();
    }

    public function syncAll(?CarbonInterface $today = null): int
    {
        $updated = 0;

        Resolution::chunkById(200, function ($resolutions) use (&$updated, $today) {
            foreach ($resolutions as $resolution) {
                $updated += (int) $this->sync($resolution, $today);
            }
        });

        return $updated;
    }
}
