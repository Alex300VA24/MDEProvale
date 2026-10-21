<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePecosaRequest;
use App\Http\Requests\UpdatePecosaRequest;
use App\Models\Pecosa;
use App\Services\PecosaService;
use App\Services\PDFService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PecosaController extends Controller
{
    public function __construct(
        private PecosaService $pecosaService,
        private PDFService $pdfService
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'association_id', 'state_id', 'fecha_inicio', 'fecha_fin']);

        return view('productos-pecosas.pecosas.index', $this->pecosaService->indexData($filters));
    }

    public function create()
    {
        return view('productos-pecosas.pecosas.create', $this->pecosaService->createData());
    }

    public function store(StorePecosaRequest $request)
    {
        try {
            $this->pecosaService->createPecosa($request->validated());
            return redirect()->route('pecosas.index')
                ->with('success', 'Pecosa creada exitosamente');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al crear PECOSA: ' . $e->getMessage());
        }
    }

    public function show(Pecosa $pecosa)
    {
        $pecosa = $this->pecosaService->showPecosa($pecosa);

        return view('productos-pecosas.pecosas.show', compact('pecosa'));
    }

    public function edit(Pecosa $pecosa)
    {
        return view('productos-pecosas.pecosas.edit', ['pecosa' => $pecosa] + $this->pecosaService->editData());
    }

    public function update(UpdatePecosaRequest $request, Pecosa $pecosa)
    {
        try {
            $this->pecosaService->updatePecosa($pecosa->id, $request->validated());
            return redirect()->route('pecosas.index')
                ->with('success', 'Pecosa actualizada exitosamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar PECOSA: ' . $e->getMessage());
        }
    }

    public function destroy(Pecosa $pecosa)
    {
        try {
            $this->pecosaService->deletePecosa($pecosa);
            return redirect()->route('pecosas.index')->with('success', 'Pecosa eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar PECOSA: ' . $e->getMessage());
        }
    }

    public function generarComprobante(Pecosa $pecosa)
    {
        [, $contents, $filename] = $this->pecosaService->generateComprobante($pecosa, request()->user()?->id);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function generarPecosaCompleta(Pecosa $pecosa)
    {
        [, $contents, $filename] = $this->pecosaService->generatePecosaCompleta($pecosa);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function reportes()
    {
        return view('productos-pecosas.pecosas.reportes');
    }

    /**
     * Genera en un solo PDF el comprobante de salida de todas las PECOSAs
     * del período de repartición (mes/año) indicado.
     */
    public function pdfMensual(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2019|max:' . (now()->year + 1),
            'month' => 'required|integer|min:1|max:12',
        ]);

        $items = $this->pecosaService->getMonthlyComprobantesData((int) $data['year'], (int) $data['month']);

        if (empty($items)) {
            return response()->json(['message' => 'No hay pecosas registradas para el periodo seleccionado.'], 404);
        }

        $periodo = Carbon::create((int) $data['year'], (int) $data['month'], 1)->locale('es')->translatedFormat('F_Y');
        $filename = 'pecosas-' . Str::slug($periodo) . '.pdf';

        return $this->pdfService->stream('comprobante_salida_lote', ['items' => $items, 'mostrarValores' => false], $filename, 'a4', 'landscape');
    }

    /**
     * Genera el Acta de Supervisión en blanco (formato oficial para llenar a mano
     * durante la visita de supervisión a los clubes de madres y comités).
     */
    public function actaSupervision()
    {
        $pdf = $this->pdfService->generate('actas.supervision', [], 'a4', 'portrait');

        return $pdf->stream('acta-supervision.pdf');
    }

    /**
     * Informa si al registrar una PECOSA para el comité y la fecha indicados
     * quedará una PECOSA del período anterior que pasará a estado VENCIDA.
     * Alimenta el aviso de confirmación del formulario de registro.
     */
    public function verificarVigencia(Request $request)
    {
        $data = $request->validate([
            'association_id' => 'required|exists:associations,id',
            'delivery_date' => 'required|date',
        ]);

        return response()->json(
            $this->pecosaService->supersessionNotice((int) $data['association_id'], $data['delivery_date'])
        );
    }
}
