<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Services\Pvl\PvlRagService;
use App\Services\Pvl\PvlReportGeneratorService;
use App\Services\Pvl\PvlSupportingReportService;
use App\Services\ReportePvlPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PvlReportsController extends Controller
{
    public function index()
    {
        return response()->json([
            'runs' => PvlReportRun::query()
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (PvlReportRun $run) => $this->runResource($run)),
            'documents' => PvlDocument::query()
                ->with('product:id,title')
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (PvlDocument $document) => $this->documentResource($document)),
            'products' => Product::query()->orderBy('title')->get(['id', 'title']),
            'document_types' => PvlDocument::TYPES,
        ]);
    }

    public function analyze(Request $request, PvlReportGeneratorService $generator)
    {
        $validated = $request->validate([
            'report_type' => ['required', Rule::in(['PVL', 'RACION_A', 'AMBOS'])],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'report_metadata' => ['nullable', 'array'],
            'report_metadata.report_number' => ['nullable', 'string', 'max:80'],
            'report_metadata.recipient_name' => ['nullable', 'string', 'max:160'],
            'report_metadata.recipient_role' => ['nullable', 'string', 'max:160'],
            'report_metadata.sender_name' => ['nullable', 'string', 'max:160'],
            'report_metadata.sender_role' => ['nullable', 'string', 'max:160'],
            'report_metadata.subject' => ['nullable', 'string', 'max:300'],
            'report_metadata.place' => ['nullable', 'string', 'max:100'],
        ]);

        $run = $generator->generate(
            $validated['report_type'],
            (int) $validated['year'],
            (int) $validated['month'],
            $request->user()?->id,
            $validated['report_metadata'] ?? [],
        );

        if ($run->status === PvlReportRun::ERROR) {
            [$status, $code, $message] = $this->analysisError($run);

            return response()->json([
                'message' => $message,
                'error_code' => $code,
                'run' => $this->runResource($run),
            ], $status);
        }

        return response()->json([
            'message' => $run->getAttribute('was_cached') ? 'Se reutilizó el análisis vigente.' : 'Análisis completado.',
            'run' => $this->runResource($run),
        ]);
    }

    public function show(PvlReportRun $pvlReportRun)
    {
        return response()->json(['run' => $this->runResource($pvlReportRun)]);
    }

    public function destroyRun(PvlReportRun $pvlReportRun)
    {
        $pvlReportRun->delete();

        return response()->json(['message' => 'Análisis eliminado del historial.']);
    }

    public function storeDocument(Request $request, PvlRagService $rag)
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(PvlDocument::TYPES)],
            'period' => ['required', 'date_format:Y-m'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'provider_reference' => ['nullable', 'string', 'max:160'],
            'file' => [
                'required',
                'file',
                'max:'.(int) config('pvl_reports.max_document_kb', 10240),
                'mimes:pdf,txt,csv,json,png,jpg,jpeg',
            ],
        ]);

        $file = $request->file('file');
        $binary = file_get_contents($file->getRealPath());
        $hash = hash('sha256', $binary);

        $duplicate = PvlDocument::query()
            ->where('period', $validated['period'])
            ->where('document_type', $validated['document_type'])
            ->where('file_hash', $hash)
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Este archivo ya fue indexado para el mismo periodo y tipo documental.',
                'document' => $this->documentResource($duplicate),
            ], 422);
        }

        $document = PvlDocument::create([
            'document_type' => $validated['document_type'],
            'period' => $validated['period'],
            'product_id' => $validated['product_id'] ?? null,
            'provider_reference' => $validated['provider_reference'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'file_hash' => $hash,
            'file_data' => base64_encode(gzdeflate($binary, 9)),
            'created_by' => $request->user()?->id,
        ]);

        try {
            $rag->indexDocument($document, $binary);
        } catch (\Throwable $exception) {
            $detail = $exception instanceof AiProviderException || $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Revise que el archivo contenga texto legible y vuelva a intentarlo.';
            $status = $exception instanceof AiProviderException
                ? $exception->httpStatus()
                : ($exception instanceof \RuntimeException ? 422 : 500);

            return response()->json([
                'message' => 'Archivo guardado, pero no pudo indexarse. '.$detail,
                'error_code' => $exception instanceof AiProviderException
                    ? 'ERROR_AI_'.$exception->reason()
                    : 'ERROR_INDEXACION',
                'document' => $this->documentResource($document->fresh('product')),
            ], $status);
        }

        return response()->json([
            'message' => 'Documento indexado con trazabilidad.',
            'document' => $this->documentResource($document->fresh('product')),
        ], 201);
    }

    public function downloadDocument(PvlDocument $pvlDocument)
    {
        $decoded = base64_decode($pvlDocument->file_data);
        $binary = @gzinflate($decoded);
        if ($binary === false) {
            $binary = $decoded;
        }

        return response($binary, 200, [
            'Content-Type' => $pvlDocument->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($pvlDocument->file_name).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroyDocument(PvlDocument $pvlDocument)
    {
        $pvlDocument->delete();

        return response()->json(['message' => 'Documento y fragmentos eliminados.']);
    }

    public function markGenerated(
        PvlReportRun $pvlReportRun,
        ReportePvlPdfService $pdfService,
        PvlSupportingReportService $supportingReport,
    )
    {
        if (! $pvlReportRun->canGenerate()) {
            return response()->json([
                'message' => 'El reporte contiene hallazgos críticos y no puede generar un PDF.',
            ], 422);
        }

        $types = match ($pvlReportRun->report_type) {
            'PVL' => ['pvl'],
            'RACION_A' => ['racion-a'],
            default => ['pvl', 'racion-a', 'informe'],
        };

        foreach ($types as $type) {
            $key = $type === 'pvl' ? 'pvl' : 'racion_a';
            $data = $type === 'informe'
                ? $supportingReport->build($pvlReportRun)
                : data_get($pvlReportRun->validated_data_json, $key);
            if (! is_array($data)) {
                $pvlReportRun->forceFill(['error_message' => 'ERROR_DATOS'])->save();

                return response()->json([
                    'message' => 'Faltan datos validados para generar uno de los reportes.',
                    'error_code' => 'ERROR_DATOS',
                ], 422);
            }

            try {
                $pdfService->forType($type, $data)->output();
            } catch (\Throwable $exception) {
                Log::error('Falló la generación del PDF PVL.', [
                    'run_id' => $pvlReportRun->id,
                    'report_type' => $type,
                    'error_code' => 'ERROR_PDF',
                    'exception' => $exception::class,
                ]);
                $pvlReportRun->forceFill(['error_message' => 'ERROR_PDF'])->save();

                return response()->json([
                    'message' => 'No se pudo generar el PDF. Puede reintentarlo.',
                    'error_code' => 'ERROR_PDF',
                ], 500);
            }
        }

        $pvlReportRun->forceFill([
            'status' => PvlReportRun::GENERADO,
            'generated_at' => now(),
            'error_message' => null,
        ])->save();

        return response()->json([
            'message' => count($types) > 1
                ? 'Anexos e informe sustentatorio listos para previsualizar o descargar.'
                : 'PDF listo para previsualizar o descargar.',
            'run' => $this->runResource($pvlReportRun->fresh()),
            'files' => collect($types)->map(fn (string $type) => [
                'type' => $type,
                'preview_url' => route('reportes.pvl.preview', [$pvlReportRun, $type]),
                'download_url' => route('reportes.pvl.download', [$pvlReportRun, $type]),
            ])->values(),
        ]);
    }

    private function runResource(PvlReportRun $run): array
    {
        return [
            'id' => $run->id,
            'report_type' => $run->report_type,
            'month' => $run->month,
            'year' => $run->year,
            'status' => $run->status,
            'validated_data' => $run->validated_data_json,
            'findings' => collect($run->warnings_json ?? [])->map(function (array $finding) {
                unset($finding['_signature']);

                return $finding;
            })->values(),
            'sources' => $run->sources_json ?? [],
            'conflicts' => data_get($run->ai_output_json, 'conflictos', []),
            'missing' => data_get($run->ai_output_json, 'datos_faltantes', []),
            'observations' => data_get($run->ai_output_json, 'observaciones', []),
            'model_used' => $run->model_used,
            'prompt_version' => $run->prompt_version,
            'report_metadata' => data_get($run->input_snapshot_json, 'report_metadata', []),
            'error_message' => $run->error_message,
            'created_at' => optional($run->created_at)->format('d/m/Y H:i'),
            'generated_at' => optional($run->generated_at)->format('d/m/Y H:i'),
            'can_generate' => $run->canGenerate(),
        ];
    }

    private function analysisError(PvlReportRun $run): array
    {
        $stored = (string) $run->error_message;
        [$code, $detail] = array_pad(explode(': ', $stored, 2), 2, null);

        return match ($code) {
            'ERROR_AI_RATE_LIMIT', 'ERROR_GEMINI_RATE_LIMIT' => [
                429,
                $code,
                $detail ?: 'El proveedor de IA alcanzó su límite de uso. Vuelva a intentarlo más tarde.',
            ],
            'ERROR_AI_CONFIGURATION', 'ERROR_AI_AUTHENTICATION', 'ERROR_AI_CONNECTION', 'ERROR_AI_UNAVAILABLE',
            'ERROR_GEMINI_CONFIGURATION', 'ERROR_GEMINI_AUTHENTICATION', 'ERROR_GEMINI_CONNECTION', 'ERROR_GEMINI_UNAVAILABLE' => [
                503,
                $code,
                $detail ?: 'El proveedor de IA no está disponible o no está configurado correctamente.',
            ],
            'ERROR_AI_REQUEST_REJECTED', 'ERROR_AI_REJECTED', 'ERROR_AI',
            'ERROR_GEMINI_REQUEST_REJECTED', 'ERROR_GEMINI_REJECTED', 'ERROR_GEMINI' => [
                502,
                $code,
                $detail ?: 'El proveedor de IA no pudo completar el análisis estructurado. Revise el modelo y vuelva a intentarlo.',
            ],
            'ERROR_AI_TRANSIENT', 'ERROR_GEMINI_TRANSIENT' => [
                503,
                $code,
                $detail ?: 'El proveedor de IA falló de forma temporal al estructurar el análisis. Vuelva a intentarlo en unos momentos.',
            ],
            'ERROR_RAG' => [503, $code, 'No se pudo recuperar la evidencia documental. Vuelva a intentarlo.'],
            'ERROR_VALIDACION' => [422, $code, 'Los datos obtenidos no pudieron validarse de forma segura.'],
            default => [500, $code ?: 'ERROR', 'El análisis no pudo completarse. Puede reintentarlo.'],
        };
    }

    private function documentResource(PvlDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'period' => $document->period,
            'product' => $document->product?->title,
            'provider_reference' => $document->provider_reference,
            'file_name' => $document->file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'index_status' => $document->index_status,
            'index_error' => $document->index_error,
            'created_at' => optional($document->created_at)->format('d/m/Y H:i'),
            'download_url' => route('api.reportes-pvl.documents.download', $document),
        ];
    }
}
