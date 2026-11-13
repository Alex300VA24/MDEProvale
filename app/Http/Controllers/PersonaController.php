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
        return redirect()->route('dashboard');
    }

    public function create()
    {
        return redirect()->route('dashboard');
    }

    public function store(StorePersonaRequest $request)
    {
        $this->personService->create($request->validated());

        return redirect()->route('dashboard')->with('success', 'Persona registrada exitosamente');
    }

    public function edit(People $person)
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdatePersonaRequest $request, People $person)
    {
        $this->personService->update($person, $request->validated());

        return redirect()->route('dashboard')->with('success', 'Persona actualizada exitosamente');
    }

    public function destroy(People $person)
    {
        try {
            $this->personService->delete($person);
        } catch (\DomainException $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Persona eliminada exitosamente');
    }
}
