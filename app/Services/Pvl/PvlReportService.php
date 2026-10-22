<?php

namespace App\Services\Pvl;

use App\Exceptions\AiProviderException;
use App\Exceptions\PvlReportException;
use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Repositories\Contracts\PvlReportRepositoryInterface;
use App\Services\Rag\DocumentBinaryStorage;
use App\Services\ReportePvlPdfService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Casos de uso de la pantalla de reportes PVL / Ración A.
 */
class PvlReportService
{
    public function __construct(
        private PvlReportRepositoryInterface $reports,
        private PvlReportDataMapper $mapper,
        private PvlReportValidationService $validationService,
        private PvlSupportingReportService $supportingReport,
        private ReportePvlPdfService $pdfService,
        private PvlRagService $rag,
    ) {
    }

    /** @return array{runs:Collection,documents:Collection,products:Collection} */
    public function overview(): array
    {
        return [
            'runs' => $this->reports->recentRuns(20),
            'documents' => $this->reports->recentDocuments(30),
            'products' => $this->reports->productOptions(),
        ];
    }

    public function deleteRun(PvlReportRun $run): void
    {
        $this->reports->deleteRun($run);
    }

    /**
     * Completa campos faltantes a mano y vuelve a validar el análisis.
     *
     * @param  array<int, array{path:string,value:?string}>  $values
     *
     * @throws PvlReportException si algún campo no admite edición manual
     */
    public function updateMissingData(PvlReportRun $run, array $values): PvlReportRun
    {
        $data = $run->validated_data_json ?? [];
        $allowed = collect($this->missingFields($run))
            ->where('fillable', true)
            ->pluck('path')
            ->all();
        $updatedPaths = [];

        foreach ($values as $item) {
            $path = $item['path'];
            if (! in_array($path, $allowed, true) || ! Arr::has($data, $path)) {
                throw new PvlReportException(
                    'Uno de los campos no puede completarse manualmente en este análisis.',
                    422,
                    null,
                    ['values' => ['Campo no permitido: '.$path]],
                );
            }

            $value = trim((string) ($item['value'] ?? ''));
            data_set($data, $path, $value === '' ? null : $value);
            $updatedPaths[] = $path;
        }

        $normalized = $this->mapper->map(['data' => $data], [], $run->report_type);
        $context = $run->input_snapshot_json ?? [];
        $aiOutput = $run->ai_output_json ?? [];
        $result = $this->validationService->validate($normalized, $aiOutput, $context);
        $sources = collect($run->sources_json ?? [])
            ->reject(fn (array $source) => ($source['origen'] ?? null) === 'ENTRADA_USUARIO'
                && in_array($source['campo'] ?? null, $updatedPaths, true));

        foreach ($updatedPaths as $path) {
            $sources->push([
                'campo' => $path,
                'valor' => data_get($result['data'], $path),
                'origen' => 'ENTRADA_USUARIO',
                'referencia' => 'Corrección posterior al análisis',
            ]);
        }

        return $this->reports->updateRun($run, [
            'validated_data_json' => $result['data'],
            'warnings_json' => $result['findings'],
            'sources_json' => $sources->values()->all(),
            'status' => $result['status'],
            'generated_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Guarda e indexa un documento de respaldo.
     *
     * @param  array{document_type:string,period:string,product_id?:?int,provider_reference?:?string}  $attributes
     * @param  array{name:string,mime:string,size:int}  $file
     *
     * @throws PvlReportException si el archivo está duplicado o no pudo indexarse
     */
    public function storeDocument(array $attributes, array $file, string $binary, ?int $userId): PvlDocument
    {
        $hash = hash('sha256', $binary);

        $duplicate = $this->reports->findDuplicateDocument($attributes['period'], $attributes['document_type'], $hash);
        if ($duplicate) {
            throw new PvlReportException(
                'Este archivo ya fue indexado para el mismo periodo y tipo documental.',
                422,
                null,
                [],
                $duplicate,
            );
        }

        $document = $this->reports->createDocument([
            'document_type' => $attributes['document_type'],
            'period' => $attributes['period'],
            'product_id' => $attributes['product_id'] ?? null,
            'provider_reference' => $attributes['provider_reference'] ?? null,
            'file_name' => $file['name'],
            'mime_type' => $file['mime'],
            'file_size' => $file['size'],
            'file_hash' => $hash,
            'file_path' => DocumentBinaryStorage::put('rag/pvl', $hash, $binary),
            'file_data' => null,
            'created_by' => $userId,
        ]);

        try {
            $this->rag->indexDocument($document, $binary);
        } catch (\Throwable $exception) {
            $detail = $exception instanceof AiProviderException || $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Revise que el archivo contenga texto legible y vuelva a intentarlo.';
            $status = $exception instanceof AiProviderException
                ? $exception->httpStatus()
                : ($exception instanceof \RuntimeException ? 422 : 500);

            throw new PvlReportException(
                'Archivo guardado, pero no pudo indexarse. '.$detail,
                $status,
                $exception instanceof AiProviderException
                    ? 'ERROR_AI_'.$exception->reason()
                    : 'ERROR_INDEXACION',
                [],
                $this->reports->freshDocument($document),
            );
        }

        return $this->reports->freshDocument($document);
    }

    public function documentBinary(PvlDocument $document): ?string
    {
        return DocumentBinaryStorage::get($document->file_path, $document->file_data);
    }

    public function deleteDocument(PvlDocument $document): void
    {
        DocumentBinaryStorage::delete($document->file_path);
        $this->reports->deleteDocument($document);
    }

    /**
     * Comprueba que cada PDF del análisis pueda generarse y marca la corrida como generada.
     *
     * @throws PvlReportException si el análisis no es generable o falla algún PDF
     */
    public function markGenerated(PvlReportRun $run): PvlReportRun
    {
        if (! $run->canGenerate()) {
            throw new PvlReportException('El reporte contiene hallazgos críticos y no puede generar un PDF.');
        }

        foreach ($this->reportTypes($run) as $type) {
            $key = $type === 'pvl' ? 'pvl' : 'racion_a';
            $data = $type === 'informe'
                ? $this->supportingReport->build($run)
                : data_get($run->validated_data_json, $key);
            if (! is_array($data)) {
                $this->reports->updateRun($run, ['error_message' => 'ERROR_DATOS']);

                throw new PvlReportException('Faltan datos validados para generar uno de los reportes.', 422, 'ERROR_DATOS');
            }

            try {
                $this->pdfService->forType($type, $data)->output();
            } catch (\Throwable $exception) {
                Log::error('Falló la generación del PDF PVL.', [
                    'run_id' => $run->id,
                    'report_type' => $type,
                    'error_code' => 'ERROR_PDF',
                    'exception' => $exception::class,
                ]);
                $this->reports->updateRun($run, ['error_message' => 'ERROR_PDF']);

                throw new PvlReportException('No se pudo generar el PDF. Puede reintentarlo.', 500, 'ERROR_PDF');
            }
        }

        return $this->reports->updateRun($run, [
            'status' => PvlReportRun::GENERADO,
            'generated_at' => now(),
            'error_message' => null,
        ]);
    }

    /** @return list<string> tipos de PDF que produce la corrida */
    public function reportTypes(PvlReportRun $run): array
    {
        return match ($run->report_type) {
            'PVL' => ['pvl'],
            'RACION_A' => ['racion-a'],
            default => ['pvl', 'racion-a', 'informe'],
        };
    }

    public function missingFields(PvlReportRun $run): array
    {
        $codes = [
            'DATO_FALTANTE', 'DATO_ADMINISTRATIVO_FALTANTE', 'BENEFICIARIOS_INCOMPLETOS',
            'RACION_FALTANTE', 'DISTRIBUCION_FALTANTE', 'CERTIFICADO_FALTANTE',
            'COMPOSICION_FALTANTE', 'COMPOSICION_INCOMPLETA',
        ];
        $data = $run->validated_data_json ?? [];

        return collect($run->warnings_json ?? [])
            ->filter(fn (array $finding) => in_array($finding['code'] ?? null, $codes, true))
            ->unique(fn (array $finding) => ($finding['field'] ?? '').'|'.($finding['code'] ?? ''))
            ->map(function (array $finding) use ($data) {
                $path = (string) ($finding['field'] ?? '');
                $isExactPath = $path !== ''
                    && ! str_contains($path, '|')
                    && preg_match('/^(pvl|racion_a)(\.[a-z0-9_]+|\.[0-9]+)+$/', $path) === 1;
                $value = $isExactPath && Arr::has($data, $path) ? data_get($data, $path) : null;
                $fillable = $isExactPath && Arr::has($data, $path) && ! is_array($value);

                return [
                    'path' => $path,
                    'label' => $this->fieldLabel($path),
                    'message' => $finding['message'] ?? 'Información no encontrada.',
                    'severity' => $finding['severity'] ?? 'ADVERTENCIA',
                    'fillable' => $fillable,
                    'value' => $fillable ? $value : null,
                ];
            })
            ->values()
            ->all();
    }

    private function fieldLabel(string $path): string
    {
        $parts = array_values(array_filter(explode('.', $path), fn (string $part) => ! ctype_digit($part)));
        $field = end($parts) ?: 'información faltante';

        return ucfirst(str_replace('_', ' ', $field));
    }
}
