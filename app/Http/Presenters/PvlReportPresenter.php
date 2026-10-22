<?php

namespace App\Http\Presenters;

use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Services\Pvl\PvlReportService;

/**
 * Da forma JSON a corridas y documentos PVL (incluye las URLs de las rutas).
 */
class PvlReportPresenter
{
    public function __construct(private PvlReportService $reports)
    {
    }

    public function run(PvlReportRun $run): array
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
            'missing_fields' => $this->reports->missingFields($run),
            'observations' => data_get($run->ai_output_json, 'observaciones', []),
            'model_used' => $run->model_used,
            'prompt_version' => $run->prompt_version,
            'report_metadata' => data_get($run->input_snapshot_json, 'report_metadata', []),
            'error_message' => $run->error_message,
            'created_at' => optional($run->created_at)->format('d/m/Y H:i'),
            'generated_at' => optional($run->generated_at)->format('d/m/Y H:i'),
            'can_generate' => $run->canGenerate(),
            'files' => $run->status === PvlReportRun::GENERADO
                ? $this->files($run)
                : [],
        ];
    }

    public function files(PvlReportRun $run): array
    {
        return collect($this->reports->reportTypes($run))->map(fn (string $type) => [
            'type' => $type,
            'preview_url' => route('reportes.pvl.preview', [$run, $type]),
            'download_url' => route('reportes.pvl.download', [$run, $type]),
        ])->values()->all();
    }

    public function document(PvlDocument $document): array
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
