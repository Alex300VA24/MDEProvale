<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\Directive;
use App\Models\Position;
use App\Repositories\Contracts\DirectiveRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DirectiveRepository implements DirectiveRepositoryInterface
{
    public function committeeResolutionIds(Association $association): Collection
    {
        $resolutionIds = DB::table('resolution_associations')
            ->where('association_id', $association->id)
            ->pluck('resolution_id');

        if ($association->resolution_id) {
            $resolutionIds->push($association->resolution_id);
        }

        return $resolutionIds->unique();
    }

    public function firstOrCreatePosition(string $title): Position
    {
        return Position::firstOrCreate(['title' => $title]);
    }

    public function positionsByTitles(Collection|array $titles): Collection
    {
        return Position::whereIn('title', $titles)->get()->keyBy('title');
    }

    public function expireCurrent(Collection $partnerIds, Collection $resolutionIds, int $positionId, int $currentStateId, int $expiredStateId, string $dateEnd): int
    {
        return Directive::whereIn('partner_id', $partnerIds)
            ->whereIn('resolution_id', $resolutionIds)
            ->where('position_id', $positionId)
            ->where('state_id', $currentStateId)
            ->update(['state_id' => $expiredStateId, 'date_end' => $dateEnd]);
    }

    public function create(array $data): Directive
    {
        return Directive::create($data);
    }

    public function hasForResolution(int $resolutionId): bool
    {
        return Directive::where('resolution_id', $resolutionId)->exists();
    }

    public function currentCommitteePositions(Collection $partnerIds, int $currentStateId): array
    {
        return Directive::query()
            ->with('position:id,title')
            ->whereIn('partner_id', $partnerIds)
            ->where('state_id', $currentStateId)
            ->whereHas('position', fn ($query) => $query->whereIn('title', Position::COMMITTEE_TITLES))
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($directive) => $directive->position)
            ->unique('position_id')
            ->mapWithKeys(fn ($directive) => [
                $directive->position->title => $directive->partner_id,
            ])
            ->all();
    }
}
