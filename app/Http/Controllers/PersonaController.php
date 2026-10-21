<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\People;
use App\Services\PersonService;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function __construct(private PersonService $personService)
    {
    }

    public function index(Request $request)
    {
        $data = $this->personService->indexData($request->only(['search', 'gender', 'place_sector_id']));

        return view('socios-beneficiarios.personas.index', $data);
    }

    public function create()
    {
        return view('socios-beneficiarios.personas.create', ['placeSectors' => $this->personService->placeSectors()]);
    }

    public function store(StorePersonaRequest $request)
    {
        $this->personService->create($request->validated());

        return redirect()->route('personas.index')->with('success', 'Persona registrada exitosamente');
    }

    public function edit(People $person)
    {
        return view('socios-beneficiarios.personas.edit', [
            'person' => $person,
            'placeSectors' => $this->personService->placeSectors(),
        ]);
    }

    public function update(UpdatePersonaRequest $request, People $person)
    {
        $this->personService->update($person, $request->validated());

        return redirect()->route('personas.index')->with('success', 'Persona actualizada exitosamente');
    }

    public function destroy(People $person)
    {
        try {
            $this->personService->delete($person);
        } catch (\DomainException $e) {
            return redirect()->route('personas.index')->with('error', $e->getMessage());
        }

        return redirect()->route('personas.index')->with('success', 'Persona eliminada exitosamente');
    }
}
