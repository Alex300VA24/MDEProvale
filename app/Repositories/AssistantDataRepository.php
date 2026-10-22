<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\Pecosa;
use App\Repositories\Contracts\AssistantDataRepositoryInterface;
use Illuminate\Support\Collection;

class AssistantDataRepository implements AssistantDataRepositoryInterface
{
    public function committeeCount(): int
    {
        return Association::count();
    }

    public function committeeCountWithState(int $stateId): int
    {
        return Association::where('state_id', $stateId)->count();
    }

    public function committees(): Collection
    {
        return Association::query()->get(['id', 'name', 'code', 'state_id']);
    }

    public function pecosaCountBetween(string $from, string $to): int
    {
        return Pecosa::whereBetween('delivery_date', [$from, $to])->count();
    }

    public function beneficiaryCount(): int
    {
        return Beneficiarie::count();
    }

    public function partnerCount(): int
    {
        return Partner::count();
    }

    public function partnerCountOfCommittee(int $committeeId): int
    {
        return Partner::where('association_id', $committeeId)->count();
    }

    public function beneficiaryCountOfCommittee(int $committeeId): int
    {
        return Beneficiarie::whereHas(
            'partner',
            fn ($partner) => $partner->where('association_id', $committeeId)
        )->count();
    }

    public function partnersMatchingIdentity(string $identity): Collection
    {
        return Partner::query()
            ->with([
                'people:id,names,father_lastname,mother_lastname,dni',
                'association:id,name,code',
                'beneficiaries.person:id,names,father_lastname,mother_lastname',
                'beneficiaries.relationship:id,title',
            ])
            ->whereHas('people', fn ($people) => $people->searchIdentity($identity))
            ->get();
    }
}
