<?php

namespace App\Http\Controllers;

use App\Models\Pecosa;
use App\Services\PresidentCommitteeResolver;
use App\Services\ReparticionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PresidentPortalController extends Controller
{
    public function __construct(
        private PresidentCommitteeResolver $committeeResolver,
        private ReparticionService $reparticionService
    ) {
    }

    public function index(Request $request)
    {
        if ($request->user()->mustChangePassword()) {
            return redirect()->route('president.password.change');
        }

        $assignment = $this->committeeResolver->resolveAssignment($request->user());
        $association = $assignment?->partner?->association;
        $year = min(max((int) $request->integer('year', now()->year), now()->year - 5), now()->year + 2);
        $month = min(max((int) $request->integer('month', now()->month), 1), 12);
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $scheduledPecosa = null;
        $allocation = null;
        $history = null;

        if ($association) {
            $scheduledPecosa = Pecosa::with(['state', 'detailPecosas'])
                ->where('association_id', $association->id)
                ->whereBetween('delivery_date', [$periodStart, $periodEnd])
                ->when($assignment->date_start, fn ($query, $date) => $query->whereDate('delivery_date', '>=', $date))
                ->when($assignment->date_end, fn ($query, $date) => $query->whereDate('delivery_date', '<=', $date))
                ->orderBy('delivery_date')
                ->first();

            $racion = $this->reparticionService->getActiveRacion($year);
            if ($racion) {
                $allocation = $this->reparticionService
                    ->buildReport($racion, $year, $month)['associations']
                    ->firstWhere('id', $association->id);
            }

            $history = Pecosa::with(['state', 'detailPecosas'])
                ->where('association_id', $association->id)
                ->when($assignment->date_start, fn ($query, $date) => $query->whereDate('delivery_date', '>=', $date))
                ->when($assignment->date_end, fn ($query, $date) => $query->whereDate('delivery_date', '<=', $date))
                ->orderByDesc('delivery_date')
                ->paginate(10)
                ->withQueryString();
        }

        return view('portal-presidentas.index', compact(
            'association',
            'assignment',
            'year',
            'month',
            'periodStart',
            'scheduledPecosa',
            'allocation',
            'history'
        ));
    }
}
