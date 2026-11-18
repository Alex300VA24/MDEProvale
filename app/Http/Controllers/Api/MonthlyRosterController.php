<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyRoster;
use App\Services\MonthlyRosterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonthlyRosterController extends Controller
{
    public function __construct(private MonthlyRosterService $rosters)
    {
    }

    public function index()
    {
        return response()->json([
            'data' => $this->rosters->index(),
            'closed_options' => $this->rosters->closedOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'source_roster_id' => ['nullable', 'integer', Rule::exists('monthly_rosters', 'id')->where('status', MonthlyRoster::CLOSED)],
        ]);

        $roster = $this->rosters->createDraft(
            $validated['period'] . '-01',
            $validated['source_roster_id'] ?? null,
            $request->user()?->id
        );

        return response()->json(['data' => $roster, 'message' => 'Padrón borrador creado.'], 201);
    }

    public function show(MonthlyRoster $monthlyRoster)
    {
        return response()->json(['data' => $this->rosters->detail($monthlyRoster)]);
    }

    public function sync(MonthlyRoster $monthlyRoster)
    {
        return response()->json([
            'data' => $this->rosters->sync($monthlyRoster),
            'message' => 'El borrador se actualizó con los datos vigentes del período.',
        ]);
    }

    public function close(Request $request, MonthlyRoster $monthlyRoster)
    {
        return response()->json([
            'data' => $this->rosters->close($monthlyRoster, $request->user()?->id),
            'message' => 'Padrón cerrado. La fotografía mensual quedó protegida.',
        ]);
    }

    public function revise(Request $request, MonthlyRoster $monthlyRoster)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        return response()->json([
            'data' => $this->rosters->revise($monthlyRoster, $validated['reason'], $request->user()?->id),
            'message' => 'Se creó una revisión sin alterar la versión cerrada.',
        ], 201);
    }

    public function destroy(MonthlyRoster $monthlyRoster)
    {
        $this->rosters->deleteDraft($monthlyRoster);

        return response()->json(['message' => 'El borrador del padrón fue eliminado.']);
    }
}
