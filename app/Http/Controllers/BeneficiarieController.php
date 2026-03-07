<?php

namespace App\Http\Controllers;

use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\Relationship;
use Illuminate\Http\Request;

class BeneficiarieController extends Controller
{
    public function index(Request $request)
    {
        $query = Beneficiarie::with(['person', 'partner', 'relationship']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('person', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                  ->orWhere('father_lastname', 'like', "%{$search}%")
                  ->orWhere('mother_lastname', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->has('partner_id') && $request->partner_id != '') {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->has('relationship_id') && $request->relationship_id != '') {
            $query->where('relationship_id', $request->relationship_id);
        }

        $beneficiaries = $query->orderBy('id', 'desc')->paginate(10);
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();

        return view('beneficiarios.index', compact('beneficiaries', 'partners', 'relationships'));
    }

    public function create()
    {
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();
        return view('beneficiarios.create', compact('partners', 'relationships'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        Beneficiarie::create($validated);
        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario creado exitosamente');
    }

    public function show(Beneficiarie $beneficiarie)
    {
        $beneficiarie->load(['person', 'partner', 'relationship']);
        return view('beneficiarios.show', compact('beneficiarie'));
    }

    public function edit(Beneficiarie $beneficiarie)
    {
        $partners = Partner::with('people')->get();
        $relationships = Relationship::all();
        return view('beneficiarios.edit', compact('beneficiarie', 'partners', 'relationships'));
    }

    public function update(Request $request, Beneficiarie $beneficiarie)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        $beneficiarie->update($validated);
        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario actualizado exitosamente');
    }

    public function destroy(Beneficiarie $beneficiarie)
    {
        $beneficiarie->delete();
        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario eliminado exitosamente');
    }

    public function reportes()
    {
        return view('beneficiarios.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Beneficiarie::with(['person', 'partner.people', 'relationship']);

        switch ($tipo) {
            case 'general':
                $beneficiaries = $query->get();
                $titulo = 'Listado General de Beneficiarios';
                break;
            case 'socio':
                $partnerId = $request->get('partner_id');
                $beneficiaries = $query->where('partner_id', $partnerId)->get();
                $partner = Partner::with('people')->find($partnerId);
                $titulo = 'Beneficiarios del Socio: ' . ($partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'N/A');
                break;
            case 'relacion':
                $relationshipId = $request->get('relationship_id');
                $beneficiaries = $query->where('relationship_id', $relationshipId)->get();
                $relationship = \App\Models\Relationship::find($relationshipId);
                $titulo = 'Beneficiarios - Relación: ' . ($relationship->title ?? 'N/A');
                break;
            case 'estadistico':
                $beneficiaries = $query->get();
                $titulo = 'Reporte Estadístico de Beneficiarios';
                break;
            default:
                $beneficiaries = $query->get();
                $titulo = 'Reporte de Beneficiarios';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.beneficiarios', compact('beneficiaries', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-beneficiarios-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    public function imprimir()
    {
        $logoPath = public_path('img/muni2.png');
        $pdf = \PDF::loadView('ficha_beneficiario', compact('logoPath'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha-beneficiario-' . date('Y-m-d-His') . '.pdf');
    }
}
