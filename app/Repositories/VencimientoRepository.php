<?php

namespace App\Repositories;

use App\Models\BeneficiaryHistory;
use App\Models\Resolution;
use App\Models\State;
use App\Models\TypeBenefit;
use App\Repositories\Contracts\VencimientoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class VencimientoRepository implements VencimientoRepositoryInterface
{
    public function benefitsEndingWithin(Carbon $today, int $days): Collection
    {
        return BeneficiaryHistory::query()
            ->whereNotNull('date_end')
            ->whereDate('date_end', '>=', $today)
            ->whereDate('date_end', '<=', $today->copy()->addDays($days))
            ->whereHas('state', fn ($query) => $query->where('abbreviation', '!=', State::INACTIVE))
            ->whereHas('typeBenefit', fn ($query) => $query->whereIn('abbreviation', ['GES', 'LAC']))
            ->with(['typeBenefit', 'beneficiary.person', 'beneficiary.partner.association'])
            ->get();
    }

    public function maxAgeByBenefitType(): SupportCollection
    {
        return TypeBenefit::whereNotNull('max_age')->pluck('max_age', 'id');
    }

    public function benefitsBornBetween(int $typeBenefitId, Carbon $today, string $bornFrom, string $bornTo): Collection
    {
        return BeneficiaryHistory::query()
            ->where('type_benefit_id', $typeBenefitId)
            ->where(fn ($q) => $q->whereNull('date_end')->orWhereDate('date_end', '>=', $today))
            ->whereHas('beneficiary.person', fn ($q) => $q->whereBetween('birthdate', [$bornFrom, $bornTo]))
            ->with(['typeBenefit', 'beneficiary.person', 'beneficiary.partner.association'])
            ->get();
    }

    public function resolutionsEndingBetween(Carbon $from, Carbon $to): Collection
    {
        return Resolution::query()
            ->whereNotNull('date_end')
            ->whereDate('date_end', '>=', $from)
            ->whereDate('date_end', '<=', $to)
            ->with('primaryAssociations:id,name,resolution_id')
            ->get();
    }
}
