<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Association;
use App\Models\State;
use App\Models\People;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::with(['people', 'association', 'state']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('people', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                  ->orWhere('father_lastname', 'like', "%{$search}%")
                  ->orWhere('mother_lastname', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $partners = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();

        return view('socios.index', compact('partners', 'associations', 'states'));
    }

    public function create()
    {
        $people = People::whereDoesntHave('partners')->get();

        $associations = Association::all();
        $states = State::all();
        return view('socios.create', compact('people', 'associations', 'states'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
        ]);

        Partner::create($validated);
        return redirect()->route('socios.index')->with('success', 'Socio creado exitosamente');
    }

    public function show(Partner $partner)
    {
        $partner->load(['people', 'association', 'state', 'beneficiaries', 'directives']);
        return view('socios.show', compact('partner'));
    }

    public function edit(Partner $partner)
    {
        $people = People::all();
        $associations = Association::all();
        $states = State::all();
        return view('socios.edit', compact('partner', 'people', 'associations', 'states'));
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
        ]);

        $partner->update($validated);
        return redirect()->route('socios.index')->with('success', 'Socio actualizado exitosamente');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect()->route('socios.index')->with('success', 'Socio eliminado exitosamente');
    }

    public function reportes()
    {
        return view('socios.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Partner::with(['people', 'association', 'state']);

        switch ($tipo) {
            case 'general':
                $partners = $query->get();
                $titulo = 'Listado General de Socios';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $partners = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Socios del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'estado':
                $stateId = $request->get('state_id');
                $partners = $query->where('state_id', $stateId)->get();
                $state = \App\Models\State::find($stateId);
                $titulo = 'Socios - Estado: ' . ($state->title ?? 'N/A');
                break;
            case 'fecha':
                $fechaInicio = $request->get('fecha_inicio');
                $fechaFin = $request->get('fecha_fin');
                $partners = $query->whereBetween('date_begin', [$fechaInicio, $fechaFin])->get();
                $titulo = 'Socios del ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin));
                break;
            case 'estadistico':
                $partners = $query->get();
                $titulo = 'Reporte Estadístico de Socios';
                break;
            case 'beneficiarios':
                $partners = $query->with('beneficiaries')->get();
                $titulo = 'Socios con Beneficiarios';
                break;
            default:
                $partners = $query->get();
                $titulo = 'Reporte de Socios';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.socios', compact('partners', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-socios-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
