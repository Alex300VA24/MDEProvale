<?php

namespace App\Http\Controllers;

use App\Models\Beneficiarie;
use App\Services\BeneficiaryService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class BeneficiarieController extends Controller
{
    public function __construct(private BeneficiaryService $beneficiaryService)
    {
    }

    public function index(Request $request)
    {
        $data = $this->beneficiaryService->indexData($request->only(['search', 'partner_id', 'relationship_id']));

        return view('socios-beneficiarios.beneficiarios.index', $data);
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
        $this->beneficiaryService->create($this->validated($request));

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario registrado exitosamente');
    }

    public function update(Request $request, Beneficiarie $beneficiarie)
    {
        $this->beneficiaryService->update($beneficiarie, $this->validated($request));

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario actualizado exitosamente');
    }

    public function destroy(Beneficiarie $beneficiarie)
    {
        $this->beneficiaryService->delete($beneficiarie);

        return redirect()->route('socios-beneficiarios.beneficiarios.index')->with('success', 'Beneficiario eliminado exitosamente');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ]);
    }
}
