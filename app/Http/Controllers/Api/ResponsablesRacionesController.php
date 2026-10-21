<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRacionRequest;
use App\Http\Requests\UpdateRacionRequest;
use App\Http\Requests\UpdateResponsibleRequest;
use App\Http\Resources\PersonaResource;
use App\Http\Resources\RacionResource;
use App\Http\Resources\ResponsibleHistoryResource;
use App\Http\Resources\ResponsibleResource;
use App\Models\Racion;
use App\Models\Responsible;
use App\Services\RacionService;
use App\Services\ResponsibleService;
use Illuminate\Http\Request;

class ResponsablesRacionesController extends Controller
{
    public function __construct(
        private ResponsibleService $responsibles,
        private RacionService $raciones
    ) {
    }

    public function responsibles()
    {
        $current = $this->responsibles->current();

        return response()->json([
            'chief' => $current['chief'] ? new ResponsibleResource($current['chief']) : null,
            'storekeeper' => $current['storekeeper'] ? new ResponsibleResource($current['storekeeper']) : null,
            'people' => PersonaResource::collection($current['people']),
        ]);
    }

    /**
     * Historial paginado de responsables (una fila por periodo). Soporta
     * búsqueda reactiva por nombre/DNI y filtros por tipo y vigencia.
     */
    public function responsiblesHistory(Request $request)
    {
        $history = $this->responsibles->history(
            $request->only(['search', 'type', 'vigencia']),
            (int) $request->input('per_page', 10)
        );

        return ResponsibleHistoryResource::collection($history);
    }

    public function showResponsible(Responsible $responsible)
    {
        $this->authorize('viewAny', Racion::class);

        $responsible = $this->responsibles->detail($responsible);

        $data = (new ResponsibleHistoryResource($responsible))->resolve();
        $data['person'] = $responsible->person ? (new PersonaResource($responsible->person))->resolve() : null;

        return response()->json(['data' => $data]);
    }

    public function updateResponsible(UpdateResponsibleRequest $request, string $type)
    {
        $responsible = $this->responsibles->replace($type, $request->validated()['person_id']);

        return response()->json(['data' => new ResponsibleResource($responsible)]);
    }

    /**
     * Finaliza manualmente el periodo de un responsable vigente sin asignar un
     * reemplazo. Dispara el evento de periodo terminado.
     */
    public function endResponsiblePeriod(Responsible $responsible)
    {
        $this->authorize('create', Racion::class);

        return response()->json(['data' => new ResponsibleHistoryResource($this->responsibles->endPeriod($responsible))]);
    }

    public function raciones()
    {
        return response()->json(['data' => RacionResource::collection($this->raciones->all())], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function storeRacion(StoreRacionRequest $request)
    {
        $racion = $this->raciones->create($request->validated());

        return response()->json(['data' => new RacionResource($racion)], 201, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function updateRacion(UpdateRacionRequest $request, Racion $racion)
    {
        $racion = $this->raciones->update($racion, $request->validated());

        return response()->json(['data' => new RacionResource($racion)], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function destroyRacion(Racion $racion)
    {
        $this->authorize('delete', $racion);

        $this->raciones->delete($racion);

        return response()->json(null, 204);
    }
}
