<?php

namespace App\Http\Controllers;

use App\Models\Pecosa;
use App\Models\Association;
use App\Models\State;
use App\Models\Partner;
use Illuminate\Http\Request;

class PecosaController extends Controller
{
    public function index(Request $request)
    {
        $query = Pecosa::with(['association', 'state', 'managingPartner.people']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('pecosa_number', 'like', "%{$search}%");
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $pecosas = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();
        return view('pecosas.index', compact('pecosas', 'associations', 'states'));
    }

    public function create()
    {
        $associations = Association::all();
        $states = State::all();
        $partners = Partner::with('people')->get();
        return view('pecosas.create', compact('associations', 'states', 'partners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:50',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'managing_partner_id' => 'nullable|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
        ]);
        Pecosa::create($validated);
        return redirect()->route('pecosas.index')->with('success', 'Pecosa creada exitosamente');
    }

    public function show(Pecosa $pecosa)
    {
        return view('pecosas.show', compact('pecosa'));
    }

    public function edit(Pecosa $pecosa)
    {
        $associations = Association::all();
        $states = State::all();
        $partners = Partner::with('people')->get();
        return view('pecosas.edit', compact('pecosa', 'associations', 'states', 'partners'));
    }

    public function update(Request $request, Pecosa $pecosa)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:50',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'managing_partner_id' => 'nullable|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
        ]);
        $pecosa->update($validated);
        return redirect()->route('pecosas.index')->with('success', 'Pecosa actualizada exitosamente');
    }

    public function destroy(Pecosa $pecosa)
    {
        $pecosa->delete();
        return redirect()->route('pecosas.index')->with('success', 'Pecosa eliminada exitosamente');
    }

    public function reportes()
    {
        return view('pecosas.reportes');
    }

    public function generarReporte($tipo, Request $request)
    {
        $query = Pecosa::with(['association', 'state', 'managingPartner.people']);

        switch ($tipo) {
            case 'general':
                $pecosas = $query->get();
                $titulo = 'Todas las Pecosas';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $pecosas = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Pecosas del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'fecha':
                $fechaInicio = $request->get('fecha_inicio');
                $fechaFin = $request->get('fecha_fin');
                $pecosas = $query->whereBetween('delivery_date', [$fechaInicio, $fechaFin])->get();
                $titulo = 'Pecosas del ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin));
                break;
            case 'detalle':
                $pecosas = $query->with('detailPecosas.product')->get();
                $titulo = 'Pecosas con Detalle de Productos';
                break;
            case 'estadistico':
                $pecosas = $query->get();
                $titulo = 'Reporte Estadístico de Pecosas';
                break;
            case 'responsable':
                $partnerId = $request->get('partner_id');
                $pecosas = $query->where('managing_partner_id', $partnerId)->get();
                $partner = Partner::with('people')->find($partnerId);
                $titulo = 'Pecosas del Responsable: ' . ($partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'N/A');
                break;
            default:
                $pecosas = $query->get();
                $titulo = 'Reporte de Pecosas';
        }

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('reportes.pecosas', compact('pecosas', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-pecosas-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }
}
