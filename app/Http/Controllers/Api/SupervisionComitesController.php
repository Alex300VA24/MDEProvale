<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\SupervisionOffDay;
use App\Models\SupervisionVisit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisionComitesController extends Controller
{
    /**
     * Matriz mensual de supervisión: un comité por fila, un día por columna.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $start = Carbon::create($data['year'], $data['month'], 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $associations = Association::with(['placeSector.place', 'placeSector.sector'])
            ->whereHas('resolution')
            ->orderBy('code')
            ->get();

        $visits = SupervisionVisit::whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
            ->get(['association_id', 'visit_date', 'result'])
            ->groupBy('association_id');

        $rows = $associations->map(function (Association $association) use ($visits) {
            $presidenta = $association->getPresidenta();
            $person = $presidenta?->people;

            return [
                'id' => $association->id,
                'code' => $association->code,
                'name' => $association->name,
                'president' => $person
                    ? trim("{$person->names} {$person->father_lastname} {$person->mother_lastname}")
                    : '',
                'address' => $association->address,
                'sector' => $association->placeSector
                    ? trim(($association->placeSector->place?->title ?? '') . ' ' . ($association->placeSector->sector?->title ?? ''))
                    : '',
                'beneficiaries' => $presidenta ? $this->countBeneficiaries($association) : 0,
                'visits' => ($visits[$association->id] ?? collect())
                    ->mapWithKeys(fn ($v) => [$v->visit_date->format('Y-m-d') => $v->result])
                    ->all(),
            ];
        })->values();

        $offDays = SupervisionOffDay::whereBetween('off_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn ($d) => [$d->off_date->format('Y-m-d') => $d->reason ?? ''])
            ->all();

        return response()->json(['rows' => $rows, 'off_days' => (object) $offDays]);
    }

    /**
     * Marca un día: SI / NO. Sin resultado borra la marca.
     */
    public function setVisit(Request $request, Association $association): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'result' => ['nullable', 'in:SI,NO'],
        ]);

        if (empty($data['result'])) {
            SupervisionVisit::where('association_id', $association->id)
                ->whereDate('visit_date', $data['date'])
                ->delete();
        } else {
            SupervisionVisit::updateOrCreate(
                ['association_id' => $association->id, 'visit_date' => $data['date']],
                ['result' => $data['result'], 'user_id' => $request->user()?->id]
            );
        }

        return response()->json(['date' => $data['date'], 'result' => $data['result'] ?? null]);
    }

    /**
     * Marca o desmarca un día sin supervisión (feriado, falta de combustible, etc.).
     */
    public function setOffDay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'off' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:60'],
        ]);

        if ($data['off']) {
            SupervisionOffDay::updateOrCreate(
                ['off_date' => $data['date']],
                ['reason' => $data['reason'] ?? null, 'user_id' => $request->user()?->id]
            );
        } else {
            SupervisionOffDay::whereDate('off_date', $data['date'])->delete();
        }

        return response()->json(['date' => $data['date'], 'off' => $data['off']]);
    }

    private function countBeneficiaries(Association $association): int
    {
        return \App\Models\Beneficiarie::whereIn(
            'partner_id',
            $association->partners()->select('id')
        )->count();
    }
}
