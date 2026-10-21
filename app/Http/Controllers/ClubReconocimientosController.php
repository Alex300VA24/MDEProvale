<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Resolution;
use App\Services\ClubReportService;
use App\Services\CommitteeService;
use App\Services\MunicipalResolutionService;
use App\Services\ResolutionService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class ClubReconocimientosController extends Controller
{
    public function __construct(
        private CommitteeService $committees,
        private ResolutionService $resolutions,
        private ClubReportService $reports
    ) {
    }

    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        return view(
            'club-reconocimientos.index',
            $this->committees->indexData($request->only(['search', 'state_id', 'resolution_id']))
        );
    }

    // ==================== CLUB DE MADRES ====================

    public function storeClub(Request $request)
    {
        $data = $request->validate([
            'resolution_id' => 'required|exists:resolutions,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'company_name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'place_sector_id' => 'required|exists:place_sectors,id',
            'type_premises_id' => 'required|exists:type_premises,id',
            'observation' => 'nullable|string',
        ]);

        try {
            $this->committees->create(collect($data)->except('phone')->all());

            return redirect()->route('club-reconocimientos.index')->with('success', 'Comité registrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function updateClub(Request $request, Association $association)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:150',
            'code' => 'required|string|max:20',
            'resolution_id' => 'nullable|exists:resolutions,id',
            'place_sector_id' => 'nullable|exists:place_sectors,id',
            'type_premises_id' => 'nullable|exists:type_premises,id',
            'observation' => 'nullable|string',
        ]);

        $this->committees->update($association, $validated);

        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres actualizado exitosamente');
    }

    public function destroyClub(Association $association)
    {
        try {
            $this->committees->delete($association);
        } catch (\DomainException $e) {
            return redirect()->route('club-reconocimientos.index')
                ->with('error', 'No se puede eliminar el comité porque tiene socios, pecosas o historial de resoluciones asociado.');
        }

        return redirect()->route('club-reconocimientos.index')->with('success', 'Club de Madres eliminado exitosamente');
    }

    // ==================== RESOLUCIONES ====================

    public function indexReconocimientos(Request $request)
    {
        return view(
            'club-reconocimientos.reconocimientos.index',
            $this->resolutions->indexData($request->only(['search', 'state_id', 'anio']))
        );
    }

    public function storeReconocimiento(Request $request)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        $this->resolutions->create($validated);

        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución creada exitosamente');
    }

    public function updateReconocimiento(Request $request, Resolution $resolution)
    {
        $validated = $request->validate([
            'document' => 'required|string|max:255',
            'date_document' => 'required|date',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        $this->resolutions->update($resolution, $validated);

        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución actualizada exitosamente');
    }

    public function destroyReconocimiento(Resolution $resolution)
    {
        try {
            $this->resolutions->delete($resolution);
        } catch (\DomainException $e) {
            return redirect()->route('club-reconocimientos.reconocimientos.index')
                ->with('error', 'No se puede eliminar la resolución porque tiene comités o directivas asociadas.');
        }

        return redirect()->route('club-reconocimientos.reconocimientos.index')->with('success', 'Resolución eliminada exitosamente');
    }

    /**
     * Asignar presidenta a un comité.
     * La vigencia de la asociación depende únicamente de su resolución.
     */
    public function asignarPresidenta(Request $request, Association $association)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
        ]);

        try {
            $this->committees->assignPresident($association, (int) $request->partner_id);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', rtrim($e->getMessage(), '.') . '.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al asignar presidenta: ' . $e->getMessage());
        }

        return redirect()->route('club-reconocimientos.index')
            ->with('success', 'Presidenta asignada exitosamente.');
    }

    // ==================== RESOLUCIÓN EXTERNA (Portal Municipal) ====================

    /**
     * Busca la resolución en el portal de transparencia de la Municipalidad
     * y confirma si el PDF existe, antes de abrir el modal de vista previa.
     */
    public function buscarResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        $match = $municipal->resolve($resolution);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró esta resolución en el portal de la Municipalidad de La Esperanza.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'titulo' => $match['titulo'],
            'fecha' => $match['fecha'],
            'preview_url' => route('club-reconocimientos.reconocimientos.externa.preview', $resolution),
            'download_url' => route('club-reconocimientos.reconocimientos.externa.descargar', $resolution),
        ]);
    }

    public function previewResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        return $this->streamResolucionExterna($resolution, $municipal, 'inline');
    }

    public function descargarResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal)
    {
        return $this->streamResolucionExterna($resolution, $municipal, 'attachment');
    }

    private function streamResolucionExterna(Resolution $resolution, MunicipalResolutionService $municipal, string $disposition)
    {
        try {
            $pdf = $municipal->fetchPdf($resolution);
        } catch (\RuntimeException $e) {
            abort(502, $e->getMessage());
        }

        abort_if(!$pdf, 404, 'No se encontró esta resolución en el portal de la Municipalidad.');

        return response($pdf['body'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $pdf['filename'] . '"',
        ]);
    }

    // ==================== REPORTES ====================

    /**
     * Generar Padrón de Club de Madres con Resoluciones de Reconocimiento — HISTÓRICO.
     * Filtra comités, presidenta y beneficiarios vigentes en el mes/año consultado.
     */
    public function generarPadronClub(Request $request)
    {
        $mes  = (int) $request->get('month', date('n'));
        $anio = (int) $request->get('year', date('Y'));

        // Si no se envió mes/año, mostrar formulario de filtros
        if (!$request->filled('year')) {
            return view('club-reconocimientos.padron-filtros', compact('mes', 'anio'));
        }

        $pdf = PDF::loadView('padron_club', $this->reports->padron($mes, $anio))->setPaper('a4', 'landscape');

        return $pdf->stream('padron-club-madres-' . $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Genera el Acta de Renuncia en blanco (formato oficial para llenar a mano
     * cuando una dirigente del club de madres presenta su renuncia).
     */
    public function actaRenuncia()
    {
        $pdf = PDF::loadView('actas.renuncia', [])->setPaper('a4', 'portrait');

        return $pdf->stream('acta-renuncia.pdf');
    }

    public function actaSupervision()
    {
        $pdf = PDF::loadView('actas.supervision', [])->setPaper('a4', 'portrait');

        return $pdf->stream('acta-supervision.pdf');
    }

    /**
     * Genera el Acta de Reuniones: padrón de asistencia con los comités y sus
     * presidentas vigentes precargados, dejando la columna de firma en blanco
     * para el registro manual durante la reunión.
     */
    public function actaReuniones()
    {
        $pdf = PDF::loadView('actas.reuniones', $this->reports->actaReuniones())->setPaper('a4', 'portrait');

        return $pdf->stream('acta-reuniones.pdf');
    }
}
