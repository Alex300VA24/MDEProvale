<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Association;
use App\Models\State;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function index(Request $request)
    {
        $query = Award::with(['association', 'state']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('document', 'like', "%{$search}%");
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        $awards = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        return view('premios.index', compact('awards', 'associations'));
    }

    public function create()
    {
        $associations = Association::all();
        $states = State::all();
        return view('premios.create', compact('associations', 'states'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'association_id' => 'required|exists:associations,id',
            'state_id' => 'required|exists:states,id',
        ]);
        Award::create($validated);
        return redirect()->route('premios.index')->with('success', 'Reconocimiento creado exitosamente');
    }

    public function show(Award $award)
    {
        return view('premios.show', compact('award'));
    }

    public function edit(Award $award)
    {
        $associations = Association::all();
        $states = State::all();
        return view('premios.edit', compact('award', 'associations', 'states'));
    }

    public function update(Request $request, Award $award)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'association_id' => 'required|exists:associations,id',
            'state_id' => 'required|exists:states,id',
        ]);
        $award->update($validated);
        return redirect()->route('premios.index')->with('success', 'Reconocimiento actualizado exitosamente');
    }

    public function destroy(Award $award)
    {
        $award->delete();
        return redirect()->route('premios.index')->with('success', 'Reconocimiento eliminado exitosamente');
    }

    public function reportes()
    {
        return view('premios.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Award::with(['association', 'state']);

        switch ($tipo) {
            case 'general':
                $awards = $query->get();
                $titulo = 'Todos los Reconocimientos';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $awards = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Reconocimientos del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'anio':
                $anio = $request->get('anio', date('Y'));
                $awards = $query->whereYear('date_start', $anio)->get();
                $titulo = 'Reconocimientos del Año ' . $anio;
                break;
            case 'vigentes':
                $awards = $query->where('date_end', '>=', date('Y-m-d'))->get();
                $titulo = 'Reconocimientos Vigentes';
                break;
            case 'estadistico':
                $awards = $query->get();
                $titulo = 'Reporte Estadístico de Reconocimientos';
                break;
            default:
                $awards = $query->get();
                $titulo = 'Reporte de Reconocimientos';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.premios', compact('awards', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-reconocimientos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
