<?php

namespace App\Http\Controllers;

use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\Relationship;
use Barryvdh\DomPDF\Facade\PDF;
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
        $partners = Partner::select(['id', 'person_id'])->with('people:id,names,father_lastname')->get();
        $relationships = Relationship::all();

        return view('socios-beneficiarios.beneficiarios.index', compact('beneficiaries', 'partners', 'relationships'));
    }

    public function imprimirFicha()
    {
        $logoPath = public_path('img/muni2.png');
        $pdf = PDF::loadView('ficha_beneficiario', compact('logoPath'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha-beneficiario-' . date('Y-m-d-His') . '.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        Beneficiarie::create($validated);

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario registrado exitosamente');
    }

    public function update(Request $request, Beneficiarie $beneficiarie)
    {
        $validated = $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);

        $beneficiarie->update($validated);

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario actualizado exitosamente');
    }

    public function destroy(Beneficiarie $beneficiarie)
    {
        $beneficiarie->delete();

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario eliminado exitosamente');
    }
}