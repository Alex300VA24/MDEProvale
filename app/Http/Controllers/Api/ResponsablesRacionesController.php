<?php

namespace App\Http\Controllers\Api;

use App\Events\ResponsiblePeriodEnded;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRacionRequest;
use App\Http\Requests\UpdateRacionRequest;
use App\Http\Requests\UpdateResponsibleRequest;
use App\Http\Resources\PersonaResource;
use App\Http\Resources\RacionResource;
use App\Http\Resources\ResponsibleHistoryResource;
use App\Http\Resources\ResponsibleResource;
use App\Models\People;
use App\Models\Racion;
use App\Models\Responsible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResponsablesRacionesController extends Controller
{
    public function responsibles()
    {
        $responsibles = Responsible::with('person')->where('active', true)->get();
        $chief = $responsibles->firstWhere('type', 'chief');
        $storekeeper = $responsibles->firstWhere('type', 'storekeeper');

        return response()->json([
            'chief' => $chief ? new ResponsibleResource($chief) : null,
            'storekeeper' => $storekeeper ? new ResponsibleResource($storekeeper) : null,
            'people' => PersonaResource::collection(People::orderBy('names')->get()),
        ]);
    }

    /**
     * Historial paginado de responsables (una fila por periodo). Soporta
     * búsqueda reactiva por nombre/DNI y filtros por tipo y vigencia.
     */
    public function responsiblesHistory(Request $request)
    {
        $query = Responsible::with('person:id,names,father_lastname,mother_lastname,dni')
            ->searchPerson($request->input('search'))
            ->orderByDesc('active')
            ->orderByRaw('COALESCE(start_date, created_at) DESC');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('vigencia')) {
            $query->where('active', $request->input('vigencia') === 'vigente');
        }

        $history = $query->paginate((int) $request->input('per_page', 10));

        return ResponsibleHistoryResource::collection($history);
    }

    public function showResponsible(Responsible $responsible)
    {
        $this->authorize('viewAny', Racion::class);

        $responsible->load('person.placeSector.place:id,title', 'person.placeSector.sector:id,title');

        $data = (new ResponsibleHistoryResource($responsible))->resolve();
        $data['person'] = $responsible->person ? (new PersonaResource($responsible->person))->resolve() : null;

        return response()->json(['data' => $data]);
    }

    public function updateResponsible(UpdateResponsibleRequest $request, string $type)
    {
        $personId = $request->validated()['person_id'];

        $responsible = DB::transaction(function () use ($type, $personId) {
            $current = Responsible::with('person')->where('type', $type)->where('active', true)->get();

            foreach ($current as $previous) {
                $previous->update(['active' => false, 'end_date' => now()]);
                event(new ResponsiblePeriodEnded($previous, 'replaced'));
            }

            return Responsible::create([
                'person_id' => $personId,
                'type' => $type,
                'active' => true,
                'start_date' => now(),
            ]);
        });

        return response()->json(['data' => new ResponsibleResource($responsible->load('person'))]);
    }

    /**
     * Finaliza manualmente el periodo de un responsable vigente sin asignar un
     * reemplazo. Dispara el evento de periodo terminado.
     */
    public function endResponsiblePeriod(Responsible $responsible)
    {
        $this->authorize('create', Racion::class);

        if (! $responsible->active) {
            throw ValidationException::withMessages([
                'responsible' => 'Este responsable ya no está vigente.',
            ]);
        }

        $responsible->update(['active' => false, 'end_date' => now()]);
        event(new ResponsiblePeriodEnded($responsible->load('person'), 'manual'));

        return response()->json(['data' => new ResponsibleHistoryResource($responsible)]);
    }

    public function raciones()
    {
        $raciones = Racion::orderBy('year', 'desc')->get();

        return response()->json(['data' => RacionResource::collection($raciones)], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function storeRacion(StoreRacionRequest $request)
    {
        $racion = Racion::create([
            ...$request->validated(),
            'active' => true,
        ]);

        return response()->json(['data' => new RacionResource($racion)], 201, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function updateRacion(UpdateRacionRequest $request, Racion $racion)
    {
        $racion->update($request->validated());

        return response()->json(['data' => new RacionResource($racion)], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function destroyRacion(Racion $racion)
    {
        $this->authorize('delete', $racion);

        $racion->delete();

        return response()->json(null, 204);
    }
}
