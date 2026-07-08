<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\People;
use App\Models\PlaceSector;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function index(Request $request)
    {
        $query = People::select('id', 'names', 'father_lastname', 
                                'mother_lastname', 'dni', 'gender', 
                                'telephone_number', 'phone_number', 'birthdate', 'place_sector_id',
                                'address');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('names', 'like', "%{$search}%")
                ->orWhere('father_lastname', 'like', "%{$search}%")
                ->orWhere('mother_lastname', 'like', "%{$search}%")
                ->orWhere('dni', 'like', "%{$search}%");
        }

        if ($request->has('gender') && $request->gender != '') {
            $query->where('gender', $request->gender);
        }

        if ($request->has('place_sector_id') && $request->place_sector_id != '') {
            $query->where('place_sector_id', $request->place_sector_id);
        }

        $people = $query->orderBy('id')->paginate(15);

        $placeSectors = PlaceSector::select('id', 'place_id', 'sector_id')
            ->with([
                    'place:id,title',
                    'sector:id,title'
                    ])
            ->get();

        return view('socios-beneficiarios.personas.index', compact('people', 'placeSectors'));
    }

    public function create()
    {
        $placeSectors = PlaceSector::with(['place:id,title', 'sector:id,title'])->get();
        return view('socios-beneficiarios.personas.create', compact('placeSectors'));
    }

    public function store(StorePersonaRequest $request)
    {
        People::create($request->validated());
        return redirect()->route('personas.index')->with('success', 'Persona registrada exitosamente');
    }

    public function edit(People $person)
    {
        $placeSectors = PlaceSector::with(['place:id,title', 'sector:id,title'])->get();
        return view('socios-beneficiarios.personas.edit', compact('person', 'placeSectors'));
    }

    public function update(UpdatePersonaRequest $request, People $person)
    {
        $person->update($request->validated());
        return redirect()->route('personas.index')->with('success', 'Persona actualizada exitosamente');
    }

    public function destroy(People $person)
    {
        $hasPartners = $person->partners()->exists();
        $hasBeneficiaries = $person->beneficiaries()->exists();

        if ($hasPartners || $hasBeneficiaries) {
            return redirect()->route('personas.index')->with('error', 'No se puede eliminar la persona porque está asociada a un socio o beneficiario');
        }

        $person->delete();
        return redirect()->route('personas.index')->with('success', 'Persona eliminada exitosamente');
    }
}