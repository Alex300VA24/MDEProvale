<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\Resolution;
use App\Repositories\Contracts\DirectiveRepositoryInterface;
use App\Repositories\Contracts\ResolutionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ResolutionRepository implements ResolutionRepositoryInterface
{
    private const RELATIONS = [
        'state:id,title,abbreviation',
        'associations:id,code,name',
        'primaryAssociations:id,code,name,resolution_id',
    ];

    public function __construct(private DirectiveRepositoryInterface $directives)
    {
    }

    public function paginateFiltered(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Resolution::with(self::RELATIONS);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $matchCommittee = function ($associationQuery) use ($search) {
                $associationQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            };

            $query->where(function ($resolutionQuery) use ($search, $matchCommittee) {
                $resolutionQuery
                    ->where('document', 'like', "%{$search}%")
                    ->orWhereHas('associations', $matchCommittee)
                    ->orWhereHas('primaryAssociations', $matchCommittee);
            });
        }

        if (! empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (! empty($filters['anio'])) {
            $query->whereBetween('date_start', [
                $filters['anio'].'-01-01',
                $filters['anio'].'-12-31',
            ]);
        }

        if (! empty($filters['mes'])) {
            $month = (int) $filters['mes'];

            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('date_start', $month);
            }
        }

        return $query
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function availableForCommittees(?int $currentStateId, string $today, ?int $includeId = null): Collection
    {
        return Resolution::select(['id', 'document', 'date_document', 'date_start', 'date_end', 'state_id'])
            ->where(function ($q) use ($currentStateId, $today, $includeId) {
                $q->where(function ($sub) use ($currentStateId, $today) {
                    if ($currentStateId) {
                        $sub->where('state_id', $currentStateId);
                    }
                    $sub->where('date_end', '>=', $today)
                        ->whereNotIn('id', Association::whereNotNull('resolution_id')->select('resolution_id'))
                        ->whereNotIn('id', DB::table('resolution_associations')->select('resolution_id'));
                });

                if ($includeId) {
                    $q->orWhere('id', $includeId);
                }
            })
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->get();
    }

    public function startYears(): SupportCollection
    {
        return Resolution::selectRaw('YEAR(date_start) as year')
            ->whereNotNull('date_start')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }

    public function create(array $data): Resolution
    {
        return Resolution::create($data);
    }

    public function update(Resolution $resolution, array $data): bool
    {
        return $resolution->update($data);
    }

    public function delete(Resolution $resolution): bool
    {
        return (bool) $resolution->delete();
    }

    public function loadRelations(Resolution $resolution): Resolution
    {
        return $resolution->load(self::RELATIONS);
    }

    public function isReferenced(Resolution $resolution): bool
    {
        $hasPivot = DB::table('resolution_associations')
            ->where('resolution_id', $resolution->id)
            ->exists();

        $hasAssociations = Association::where('resolution_id', $resolution->id)->exists();

        return $hasPivot || $hasAssociations || $this->directives->hasForResolution($resolution->id);
    }
}
