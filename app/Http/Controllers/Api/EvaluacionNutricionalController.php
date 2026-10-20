<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Beneficiarie;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluacionNutricionalController extends Controller
{
    public function options(): JsonResponse
    {
        $associations = Association::whereHas('resolution')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json(['associations' => $associations]);
    }

    public function beneficiarios(Association $association): JsonResponse
    {
        return response()->json([
            'association' => ['id' => $association->id, 'code' => $association->code, 'name' => $association->name],
            'rows' => $this->rows($association),
        ]);
    }

    /**
     * Acta de evaluación nutricional: datos de la ficha de beneficiarios
     * precargados; peso, talla y diagnóstico quedan en blanco para el control.
     */
    public function acta(Association $association)
    {
        $association->load(['placeSector.place', 'placeSector.sector']);

        $presidenta = $association->getPresidenta()?->people;

        $pdf = PDF::loadView('actas.evaluacion_nutricional', [
            'association' => $association,
            'sector' => $association->placeSector
                ? trim(($association->placeSector->place?->title ?? '') . ' ' . ($association->placeSector->sector?->title ?? ''))
                : '',
            'presidenta' => $presidenta
                ? trim("{$presidenta->names} {$presidenta->father_lastname} {$presidenta->mother_lastname}")
                : '',
            'filas' => $this->rows($association),
            'generado' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('acta-evaluacion-nutricional-' . $association->code . '.pdf');
    }

    private function rows(Association $association): array
    {
        $beneficiaries = Beneficiarie::with(['person', 'relationship', 'histories' => fn ($q) => $q->latest('date_begin')])
            ->whereIn('partner_id', $association->partners()->select('id'))
            ->get()
            ->sortBy(fn ($b) => mb_strtoupper(trim(($b->person?->father_lastname ?? '') . ' ' . ($b->person?->mother_lastname ?? '') . ' ' . ($b->person?->names ?? ''))))
            ->values();

        $n = 0;

        return $beneficiaries->map(function (Beneficiarie $b) use (&$n) {
            $person = $b->person;
            $history = $b->histories->first();
            $age = $person ? $person->getAgeArray() : null;

            return [
                'numero' => ++$n,
                'name' => $person
                    ? mb_strtoupper(trim("{$person->father_lastname} {$person->mother_lastname}, {$person->names}"), 'UTF-8')
                    : 'SIN NOMBRE',
                'dni' => $person->dni ?? '',
                'gender' => $person->gender ?? '',
                'birthdate' => $person?->birthdate ? date('d/m/Y', strtotime($person->birthdate)) : '',
                'age' => $age ? ($age['years'] > 0 ? "{$age['years']} a {$age['months']} m" : "{$age['months']} m") : '',
                'relationship' => $b->relationship?->title ?? '',
                'weight' => $history && $history->weight > 0 ? (float) $history->weight : null,
                'height' => $history && $history->height > 0 ? (float) $history->height : null,
                'is_malnourished' => (bool) ($history?->is_malnourished),
                'is_disabled' => (bool) ($history?->is_disabled),
            ];
        })->all();
    }
}
