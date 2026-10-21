<?php

namespace App\Http\Controllers;

use App\Models\Resolution;
use App\Services\ClubReportService;
use App\Services\MunicipalResolutionService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class ClubReconocimientosController extends Controller
{
    public function __construct(private ClubReportService $reports)
    {
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
