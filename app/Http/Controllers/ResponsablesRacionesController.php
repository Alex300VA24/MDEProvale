<?php

namespace App\Http\Controllers;

use App\Models\Racion;
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

    public function index()
    {
        return view('responsables-raciones.index', [
            'responsibles' => $this->responsibles->all(),
            'people' => $this->responsibles->people(),
            'raciones' => $this->raciones->all(),
        ]);
    }

    public function updateResponsible(Request $request, $type)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
        ]);

        $this->responsibles->replace($type, (int) $request->person_id);

        $label = $type === 'chief' ? 'Jefe de Almacén' : 'Almacenero';
        return redirect()->route('responsables-raciones.index')->with('success', "{$label} actualizado correctamente.");
    }

    public function updateRacion(Request $request, Racion $racion)
    {
        $data = $request->validate([
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ]);

        $this->raciones->update($racion, $data);

        return redirect()->route('responsables-raciones.index')->with('success', 'Ración actualizada correctamente.');
    }

    public function storeRacion(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:raciones,year',
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ]);

        $this->raciones->create($data);

        return redirect()->route('responsables-raciones.index')->with('success', 'Ración creada correctamente.');
    }

    public function deleteRacion(Racion $racion)
    {
        $this->raciones->delete($racion);

        return redirect()->route('responsables-raciones.index')->with('success', 'Ración eliminada correctamente.');
    }
}
