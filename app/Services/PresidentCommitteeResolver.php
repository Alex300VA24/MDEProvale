<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Directive;
use App\Models\State;
use App\Models\User;

class PresidentCommitteeResolver
{
    public function resolve(User $user): ?Association
    {
        return $this->resolveAssignment($user)?->partner?->association;
    }

    public function resolveAssignment(User $user): ?Directive
    {
        $today = now()->toDateString();

        return Directive::query()
            ->whereHas('partner.people', fn ($query) => $query->where('dni', $user->dni))
            ->whereHas('position', fn ($query) => $query->where('title', 'like', '%PRESIDENTA%'))
            ->whereHas('state', fn ($query) => $query->where('abbreviation', State::CURRENT))
            ->where(function ($dates) use ($today) {
                $dates->whereNull('date_start')->orWhereDate('date_start', '<=', $today);
            })
            ->where(function ($dates) use ($today) {
                $dates->whereNull('date_end')->orWhereDate('date_end', '>=', $today);
            })
            ->with('partner.association.placeSector.sector')
            ->latest('date_start')
            ->first();
    }
}
