<?php

namespace App\Http\Controllers;

use App\Models\Association;
use Illuminate\Http\Request;

class AssociationController extends Controller
{
    public function index(Request $request)
    {
        $query = Association::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $associations = $query->orderBy('id', 'desc')->paginate(10);
        return view('club-de-madres.index', compact('associations'));
    }

    public function create()
    {
        return view('club-de-madres.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        Association::create($validated);
        return redirect()->route('club-de-madres.index')->with('success', 'Club de Madres creado exitosamente');
    }

    public function show(Association $association)
    {
        $association->load(['partners', 'resolutions']);
        return view('club-de-madres.show', compact('association'));
    }

    public function edit(Association $association)
    {
        return view('club-de-madres.edit', compact('association'));
    }

    public function update(Request $request, Association $association)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        $association->update($validated);
        return redirect()->route('club-de-madres.index')->with('success', 'Club de Madres actualizado exitosamente');
    }

    public function destroy(Association $association)
    {
        $association->delete();
        return redirect()->route('club-de-madres.index')->with('success', 'Club de Madres eliminado exitosamente');
    }

    public function reportes()
    {
        return view('club-de-madres.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Association::query();

        switch ($tipo) {
            case 'general':
                $associations = $query->get();
                $titulo = 'Listado General de Club de Madres';
                break;
            case 'socios':
                $associations = $query->with('partners.people')->get();
                $titulo = 'Club de Madres con Socios';
                break;
            case 'estadistico':
                $associations = $query->withCount('partners')->get();
                $titulo = 'Reporte Estadístico de Club de Madres';
                break;
            case 'reconocimientos':
                $associations = $query->with('resolutions')->get();
                $titulo = 'Club de Madres con Reconocimientos';
                break;
            default:
                $associations = $query->get();
                $titulo = 'Reporte de Club de Madres';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.club-de-madres', compact('associations', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-club-de-madres-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
