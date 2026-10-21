<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Services\NutritionalEvaluationService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\JsonResponse;

class EvaluacionNutricionalController extends Controller
{
    public function __construct(private NutritionalEvaluationService $evaluation)
    {
    }

    public function options(): JsonResponse
    {
        return response()->json(['associations' => $this->evaluation->committees()]);
    }

    public function beneficiarios(Association $association): JsonResponse
    {
        return response()->json([
            'association' => ['id' => $association->id, 'code' => $association->code, 'name' => $association->name],
            'rows' => $this->evaluation->rows($association),
        ]);
    }

    /**
     * Acta de evaluación nutricional: datos de la ficha de beneficiarios
     * precargados; peso, talla y diagnóstico quedan en blanco para el control.
     */
    public function acta(Association $association)
    {
        $data = $this->evaluation->actaData($association);

        $pdf = PDF::loadView('actas.evaluacion_nutricional', $data)->setPaper('a4', 'landscape');

        return $pdf->stream('acta-evaluacion-nutricional-'.$association->code.'.pdf');
    }
}
