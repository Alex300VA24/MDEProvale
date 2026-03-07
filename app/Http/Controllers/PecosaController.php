<?php

namespace App\Http\Controllers;

use App\Models\Pecosa;
use Illuminate\Http\Request;

class PecosaController extends Controller
{
    public function index()
    {
        $pecosas = Pecosa::all();
        return view('pecosas.index', compact('pecosas'));
    }

    public function create()
    {
        return view('pecosas.create');
    }

    public function store(Request $request)
    {
        Pecosa::create($request->all());
        return redirect()->route('pecosas.index');
    }

    public function show(Pecosa $pecosa)
    {
        return view('pecosas.show', compact('pecosa'));
    }

    public function edit(Pecosa $pecosa)
    {
        return view('pecosas.edit', compact('pecosa'));
    }

    public function update(Request $request, Pecosa $pecosa)
    {
        $pecosa->update($request->all());
        return redirect()->route('pecosas.index');
    }

    public function destroy(Pecosa $pecosa)
    {
        $pecosa->delete();
        return redirect()->route('pecosas.index');
    }
}
