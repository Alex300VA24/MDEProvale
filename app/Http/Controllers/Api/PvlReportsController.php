<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Http\Controllers\Controller;
use App\Services\Pvl\PvlQuarterlyAnalysisService;
use App\Services\Pvl\PvlQuarterlyDocumentService;
use App\Services\Pvl\PvlQuarterlyExportService;
use App\Services\Pvl\PvlQuarterlyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Módulo Reportes: informe trimestral con análisis de IA, exportable a Word y PDF. */
class PvlReportsController extends Controller
{
    public function quarterly(Request $request, PvlQuarterlyReportService $quarterly)
    {
        $period = $this->period($request);

        return response()->json($quarterly->build($period['year'], $period['quarter']));
    }

    public function analysis(Request $request, PvlQuarterlyAnalysisService $analysis)
    {
        $period = $this->period($request);

        try {
            return response()->json($analysis->analyze($period['year'], $period['quarter'], $request->boolean('refresh')));
        } catch (AiProviderException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error_code' => $exception->reason()], $exception->getCode() >= 400 ? $exception->getCode() : 502);
        } catch (\Throwable $exception) {
            Log::error('Falló el análisis con IA del informe trimestral.', ['exception' => $exception::class, 'message' => $exception->getMessage()]);

            return response()->json(['message' => 'No se pudo generar el análisis con IA. Puede reintentarlo.'], 502);
        }
    }

    public function document(Request $request, PvlQuarterlyReportService $quarterly, PvlQuarterlyDocumentService $document)
    {
        $period = $this->period($request);
        $report = $quarterly->build($period['year'], $period['quarter']);

        return response()->json([
            'letterhead' => $document->letterhead(),
            'blocks' => $document->build($report, $this->analysisInput($request), (array) $request->input('meta', [])),
        ]);
    }

    public function export(Request $request, string $format, PvlQuarterlyReportService $quarterly, PvlQuarterlyDocumentService $document, PvlQuarterlyExportService $exporter, PvlQuarterlyAnalysisService $analysis)
    {
        abort_unless(in_array($format, ['pdf', 'docx'], true), 404);
        $period = $this->period($request);
        $request->validate(['charts' => ['nullable', 'array'], 'charts.*' => ['nullable', 'string', 'max:4000000']]);

        $report = $quarterly->build($period['year'], $period['quarter']);
        // El análisis de IA se redacta al generar el documento (cacheado por cifras del periodo).
        $aiStatus = 'ok';
        $analysisData = $this->analysisInput($request);
        if ($analysisData === null && $report['has_data']) {
            @set_time_limit(180);
            try {
                $analysisData = $analysis->analyze($period['year'], $period['quarter']);
            } catch (\Throwable $exception) {
                $aiStatus = 'no-disponible';
                Log::warning('Informe trimestral exportado sin análisis de IA.', ['exception' => $exception::class, 'message' => $exception->getMessage()]);
            }
        }
        $blocks = $document->build($report, $analysisData, (array) $request->input('meta', []));
        $headers = ['X-Analisis-IA' => $aiStatus, 'Access-Control-Expose-Headers' => 'X-Analisis-IA'];
        $charts = $this->decodeCharts((array) $request->input('charts', []));
        $filename = sprintf('INFORME-PVL-%dT-%d.%s', $period['quarter'], $period['year'], $format);

        try {
            if ($format === 'pdf') {
                return $exporter->pdf($blocks, $charts)->download($filename)->withHeaders($headers);
            }

            $path = $exporter->docx($blocks, $charts);

            return response()->download($path, $filename, $headers + [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } finally {
            foreach ($charts as $file) {
                @unlink($file);
            }
        }
    }

    private function period(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'quarter' => ['required', 'integer', 'between:1,4'],
        ]);

        return ['year' => (int) $validated['year'], 'quarter' => (int) $validated['quarter']];
    }

    private function analysisInput(Request $request): ?array
    {
        $request->validate([
            'analysis' => ['nullable', 'array'],
            'analysis.resumen' => ['nullable', 'string', 'max:6000'],
            'analysis.analisis_mensual' => ['nullable', 'array', 'max:20'],
            'analysis.hallazgos' => ['nullable', 'array', 'max:20'],
            'analysis.conclusiones' => ['nullable', 'array', 'max:20'],
            'analysis.recomendaciones' => ['nullable', 'array', 'max:20'],
            'analysis.graficos' => ['nullable', 'array'],
            'analysis.graficos.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $input = $request->input('analysis');
        if (! is_array($input)) {
            return null;
        }

        $list = fn ($value) => collect(is_array($value) ? $value : [])->map(fn ($v) => trim((string) $v))->filter()->values()->all();

        return [
            'resumen' => trim((string) ($input['resumen'] ?? '')),
            'analisis_mensual' => $list($input['analisis_mensual'] ?? []),
            'hallazgos' => $list($input['hallazgos'] ?? []),
            'conclusiones' => $list($input['conclusiones'] ?? []),
            'recomendaciones' => $list($input['recomendaciones'] ?? []),
            'graficos' => array_filter(array_map(fn ($v) => trim((string) $v), (array) ($input['graficos'] ?? []))),
        ];
    }

    /** @return array<string,string> clave => ruta temporal de PNG */
    private function decodeCharts(array $charts): array
    {
        $files = [];
        foreach (array_keys(PvlQuarterlyDocumentService::CHARTS) as $key) {
            $value = $charts[$key] ?? null;
            if (! is_string($value) || ! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $value, $m)) {
                continue;
            }
            $binary = base64_decode($m[1], true);
            if ($binary === false || ! str_starts_with($binary, "\x89PNG")) {
                continue;
            }
            $path = tempnam(sys_get_temp_dir(), 'pvlc').'.png';
            file_put_contents($path, $binary);
            $files[$key] = $path;
        }

        return $files;
    }
}
