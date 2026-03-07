<?php

namespace App\Http\Controllers;

use App\Models\Resolution;
use Illuminate\Http\Request;

class ResolutionController extends Controller
{
    public function index()
    {
        $resolutions = Resolution::all();
        return view('premios.index', compact('resolutions'));
    }

    public function create()
    {
        return view('premios.create');
    }

    public function store(Request $request)
    {
        Resolution::create($request->all());
        return redirect()->route('premios.index');
    }

    public function show(Resolution $resolution)
    {
        return view('premios.show', compact('resolution'));
    }

    public function edit(Resolution $resolution)
    {
        return view('premios.edit', compact('resolution'));
    }

    public function update(Request $request, Resolution $resolution)
    {
        $resolution->update($request->all());
        return redirect()->route('premios.index');
    }

    public function destroy(Resolution $resolution)
    {
        $resolution->delete();
        return redirect()->route('premios.index');
    }
}
