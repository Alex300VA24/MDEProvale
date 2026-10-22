<?php

namespace App\Services\Pvl;

use App\Exceptions\AiProviderException;
use App\Models\PvlReportRun;
use App\Repositories\Contracts\PvlReportRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PvlReportGeneratorService
{
    public function __construct(
        private PvlReportContextService $contextService,
        private PvlRagService $ragService,
        private PvlAiReportService $aiReportService,
        private PvlReportDataMapper $mapper,
        private PvlReportValidationService $validationService,
        private PvlReportDefaultsService $defaultsService,
        private PvlReportRepositoryInterface $runs,
    ) {
    }

    public function generate(string $reportType, int $year, int $month, ?int $userId, array $reportMetadata = []): PvlReportRun
    {
        $context = $this->defaultsService->apply(
            $this->contextService->build($reportType, $year, $month),
            $reportMetadata
        );
        $previous = $this->runs->previousRun($year, $month);
        $fingerprintContext = $context;
        $fingerprintContext['_validation_dependencies']['previous_run'] = $previous ? [
            'id' => $previous->id,
            'source_fingerprint' => $previous->source_fingerprint,
            'saldo_final' => data_get($previous->validated_data_json, 'pvl.financiamiento.saldo_final'),
        ] : null;
        $fingerprint = $this->ragService->sourceFingerprint($reportType, $year, $month, $fingerprintContext);

        $cached = $this->runs->cachedRun($reportType, $year, $month, $userId, $fingerprint);

        if ($cached) {
            $cached->setAttribute('was_cached', true);

            return $cached;
        }

        $run = $this->runs->createRun([
            'report_type' => $reportType,
            'month' => $month,
            'year' => $year,
            'status' => PvlReportRun::ANALIZANDO,
            'input_snapshot_json' => $context,
            'model_used' => $this->aiReportService->modelIdentifier(),
            'prompt_version' => config('pvl_reports.prompt_version'),
            'source_fingerprint' => $fingerprint,
            'created_by' => $userId,
        ]);

        try {
            $fragments = $this->ragService->search($reportType, $year, $month);
        } catch (\Throwable $exception) {
            return $this->fail($run, 'ERROR_RAG', $exception);
        }

        try {
            $aiOutput = $this->aiReportService->analyze($reportType, $year, $month, $context, $fragments);
        } catch (AiProviderException $exception) {
            return $this->fail($run, 'ERROR_AI_'.$exception->reason(), $exception);
        } catch (\Throwable $exception) {
            return $this->fail($run, 'ERROR_AI', $exception);
        }

        try {
            $mapped = $this->mapper->map($aiOutput, $context, $reportType);
            $validation = $this->validationService->validate($mapped, $aiOutput, $context, $previous);

            $sources = array_values(array_merge(
                $this->effectiveContextSources($context, $mapped, $aiOutput),
                $aiOutput['trazabilidad'] ?? []
            ));

            return $this->runs->updateRun($run, [
                'status' => $validation['status'],
                'ai_output_json' => $aiOutput,
                'validated_data_json' => $validation['data'],
                'warnings_json' => $validation['findings'],
                'sources_json' => $sources,
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            return $this->fail($run, 'ERROR_VALIDACION', $exception);
        }
    }

    private function effectiveContextSources(array $context, array $mapped, array $aiOutput): array
    {
        $aiSourcePaths = collect($aiOutput['trazabilidad'] ?? [])
            ->filter(fn (array $source) => ($source['origen'] ?? null) !== 'PREDETERMINADO')
            ->pluck('campo')
            ->filter()
            ->all();

        return collect($context['trazabilidad'] ?? [])
            ->reject(function (array $source) use ($mapped, $aiSourcePaths) {
                if (($source['origen'] ?? null) !== 'PREDETERMINADO') {
                    return false;
                }

                $path = (string) ($source['campo'] ?? '');
                if (str_starts_with($path, 'report_metadata.')) {
                    return false;
                }
                if (in_array($path, $aiSourcePaths, true)) {
                    return true;
                }

                $final = data_get($mapped, $path);
                $default = $source['valor'] ?? null;
                if (is_numeric($final) && is_numeric($default)) {
                    return (float) $final !== (float) $default;
                }

                return (string) $final !== (string) $default;
            })
            ->values()
            ->all();
    }

    private function fail(PvlReportRun $run, string $code, \Throwable $exception): PvlReportRun
    {
        Log::error('Falló la generación inteligente de un reporte PVL.', [
            'run_id' => $run->id,
            'error_code' => $code,
            'exception' => $exception::class,
        ]);

        return $this->runs->updateRun($run, [
            'status' => PvlReportRun::ERROR,
            'error_message' => $code.': '.mb_substr($exception->getMessage(), 0, 500),
        ]);
    }
}
