<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PvlReportException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\PvlReportPresenter;
use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Services\Pvl\PvlReportDefaultsService;
use App\Services\Pvl\PvlReportGeneratorService;
use App\Services\Pvl\PvlReportService;
use App\Services\UploadSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PvlReportsController extends Controller
{
    public function __construct(
        private PvlReportService $reports,
        private PvlReportPresenter $presenter,
    ) {
    }

    public function index(PvlReportDefaultsService $defaults)
    {
        $overview = $this->reports->overview();

        return response()->json([
            'runs' => $overview['runs']->map(fn (PvlReportRun $run) => $this->presenter->run($run)),
            'documents' => $overview['documents']->map(fn (PvlDocument $document) => $this->presenter->document($document)),
            'products' => $overview['products'],
            'document_types' => PvlDocument::TYPES,
            'defaults' => $defaults->resource(),
        ]);
    }

    public function updateDefaults(Request $request, PvlReportDefaultsService $defaults)
    {
        $definitions = $defaults->definitions();
        $rules = ['values' => ['required', 'array']];
        foreach ($definitions as $key => $definition) {
            $rules['values.'.$key] = ['nullable', 'string', 'max:'.$definition['max_length']];
        }

        $validated = $request->validate($rules);

        return response()->json([
            'message' => 'Valores predeterminados actualizados. Se aplicarán antes del próximo análisis.',
            'defaults' => $defaults->update($validated['values']),
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
                'run' => $this->presenter->run($run),
            ], $status);
        }

        return response()->json([
            'message' => $run->getAttribute('was_cached') ? 'Se reutilizó el análisis vigente.' : 'Análisis completado.',
            'run' => $this->presenter->run($run),
        ]);
    }

    public function show(PvlReportRun $pvlReportRun)
    {
        return response()->json(['run' => $this->presenter->run($pvlReportRun)]);
    }

    public function destroyRun(PvlReportRun $pvlReportRun)
    {
        $this->reports->deleteRun($pvlReportRun);

        return response()->json(['message' => 'Análisis eliminado del historial.']);
    }

    public function updateMissingData(Request $request, PvlReportRun $pvlReportRun)
    {
        $validated = $request->validate([
            'values' => ['required', 'array', 'min:1'],
            'values.*.path' => ['required', 'string', 'max:220'],
            'values.*.value' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $run = $this->reports->updateMissingData($pvlReportRun, $validated['values']);
        } catch (PvlReportException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => 'Datos guardados y validados nuevamente.',
            'run' => $this->presenter->run($run),
        ]);
    }

    public function storeDocument(Request $request, UploadSettingsService $uploadSettings)
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(PvlDocument::TYPES)],
            'period' => ['required', 'date_format:Y-m'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'provider_reference' => ['nullable', 'string', 'max:160'],
            'file' => [
                'required',
                'file',
                'max:'.$uploadSettings->maxKilobytes(),
                'mimes:pdf,txt,csv,json,png,jpg,jpeg',
            ],
        ]);

        $file = $request->file('file');

        try {
            $document = $this->reports->storeDocument(
                $validated,
                [
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                ],
                file_get_contents($file->getRealPath()),
                $request->user()?->id,
            );
        } catch (PvlReportException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => 'Documento indexado con trazabilidad.',
            'document' => $this->presenter->document($document),
        ], 201);
    }

    public function downloadDocument(PvlDocument $pvlDocument)
    {
        $binary = $this->reports->documentBinary($pvlDocument);
        if ($binary === null) {
            abort(404, 'Archivo no disponible.');
        }

        return response($binary, 200, [
            'Content-Type' => $pvlDocument->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($pvlDocument->file_name).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroyDocument(PvlDocument $pvlDocument)
    {
        $this->reports->deleteDocument($pvlDocument);

        return response()->json(['message' => 'Documento y fragmentos eliminados.']);
    }

    public function markGenerated(PvlReportRun $pvlReportRun)
    {
        try {
            $run = $this->reports->markGenerated($pvlReportRun);
        } catch (PvlReportException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => count($this->reports->reportTypes($run)) > 1
                ? 'Anexos e informe sustentatorio listos para previsualizar o descargar.'
                : 'PDF listo para previsualizar o descargar.',
            'run' => $this->presenter->run($run),
            'files' => $this->presenter->files($run),
        ]);
    }

    private function fail(PvlReportException $exception)
    {
        $payload = ['message' => $exception->getMessage()];
        if ($exception->errors()) {
            $payload['errors'] = $exception->errors();
        }
        if ($exception->errorCode()) {
            $payload['error_code'] = $exception->errorCode();
        }
        if ($exception->document()) {
            $payload['document'] = $this->presenter->document($exception->document());
        }

        return response()->json($payload, $exception->status());
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
}
