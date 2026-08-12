<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRacionRequest;
use App\Http\Requests\UpdateRacionRequest;
use App\Http\Requests\UpdateResponsibleRequest;
use App\Http\Resources\PersonaResource;
use App\Http\Resources\RacionResource;
use App\Http\Resources\ResponsibleResource;
use App\Models\People;
use App\Models\Racion;
use App\Models\Responsible;

class MantenimientoController extends Controller
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

    public function updateResponsible(UpdateResponsibleRequest $request, string $type)
    {
        Responsible::where('type', $type)->update(['active' => false]);

        $responsible = Responsible::create([
            'person_id' => $request->validated()['person_id'],
            'type' => $type,
            'active' => true,
        ]);

        return response()->json(['data' => new ResponsibleResource($responsible->load('person'))]);
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
