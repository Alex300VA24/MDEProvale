<?php

namespace App\Http\Controllers;

use App\Models\Responsible;
use App\Models\People;
use App\Models\Racion;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    public function index()
    {
        $responsibles = Responsible::with('person')->get();
        $people = People::orderBy('names')->get();
        $raciones = Racion::orderBy('year', 'desc')->get();
        return view('mantenimiento.index', compact('responsibles', 'people', 'raciones'));
    }

    public function updateResponsible(Request $request, $type)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
        ]);

        Responsible::where('type', $type)->update(['active' => false]);

        Responsible::create([
            'person_id' => $request->person_id,
            'type'      => $type,
            'active'    => true,
        ]);

        $label = $type === 'chief' ? 'Jefe de Almacén' : 'Almacenero';
        return redirect()->route('mantenimiento.index')->with('success', "{$label} actualizado correctamente.");
    }

    public function updateRacion(Request $request, $id)
    {
        $request->validate([
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ]);

        $racion = Racion::findOrFail($id);
        $racion->update([
            'racion_hojuelas_gramos' => $request->racion_hojuelas_gramos,
            'racion_leche_militros' => $request->racion_leche_militros,
        ]);

        return redirect()->route('mantenimiento.index')->with('success', 'Ración actualizada correctamente.');
    }

    public function storeRacion(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:raciones,year',
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ]);

        Racion::create([
            'year' => $request->year,
            'racion_hojuelas_gramos' => $request->racion_hojuelas_gramos,
            'racion_leche_militros' => $request->racion_leche_militros,
            'active' => true,
        ]);

        return redirect()->route('mantenimiento.index')->with('success', 'Ración creada correctamente.');
    }

    public function deleteRacion($id)
    {
        $racion = Racion::findOrFail($id);
        $racion->delete();

        return redirect()->route('mantenimiento.index')->with('success', 'Ración eliminada correctamente.');
    }
}
