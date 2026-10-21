<?php

namespace App\Services;

use App\Models\Beneficiarie;
use Illuminate\Support\Carbon;

class BenefitPeriodCalculator
{
    public function fromBirthdate(?string $birthdate, ?string $abbreviation): ?string
    {
        if (! $birthdate || ! $abbreviation) {
            return null;
        }

        $birth = Carbon::parse($birthdate)->startOfDay();

        return match ($abbreviation) {
            'NI0' => $birth->copy()->addYearsNoOverflow(7)->subDay()->toDateString(),
            'NI7' => $birth->copy()->addYearsNoOverflow(14)->subDay()->toDateString(),
            'GES' => $birth->copy()->addMonthsNoOverflow(9)->subDay()->toDateString(),
            'LAC' => $birth->copy()->addYearsNoOverflow(2)->subDay()->toDateString(),
            default => null,
        };
    }

    public function fromChild(?Beneficiarie $child, string $abbreviation): ?string
    {
        return $this->fromBirthdate($child?->person?->birthdate, $abbreviation);
    }
}
