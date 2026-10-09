<?php

namespace Tests\Feature;

use App\Exceptions\AiProviderException;
use App\Models\PvlReportRun;
use App\Services\Pvl\PvlAiReportService;
use App\Services\Pvl\PvlRagService;
use App\Services\Pvl\PvlReportContextService;
use App\Services\Pvl\PvlReportDataMapper;
use App\Services\Pvl\PvlReportDefaultsService;
use App\Services\Pvl\PvlReportGeneratorService;
use App\Services\Pvl\PvlReportValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PvlReportGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_orchestrates_context_rag_ai_mapping_and_validation(): void
    {
        $context = [
            'pvl' => ['periodo' => '2026-09'],
            'trazabilidad' => [['campo' => 'beneficiarios', 'origen' => 'BD']],
        ];
        $fragments = [['content' => 'Factura F001', 'metadata' => ['document_id' => 10]]];
        $aiOutput = [
            'datos' => ['factura' => 'F001'],
            'trazabilidad' => [['campo' => 'factura', 'origen' => 'RAG']],
        ];
        $mapped = ['pvl' => [
            'periodo' => '2026-09',
            'factura' => 'F001',
            'municipalidad' => config('pvl_reports.municipality.name'),
            'tipo_municipalidad' => config('pvl_reports.municipality.type'),
            'departamento' => config('pvl_reports.municipality.department'),
            'provincia' => config('pvl_reports.municipality.province'),
        ]];

        $contextService = Mockery::mock(PvlReportContextService::class);
        $contextService->shouldReceive('build')->once()->with('PVL', 2026, 9)->andReturn($context);
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldReceive('sourceFingerprint')
            ->once()
            ->withArgs(fn ($type, $year, $month, $value) => $type === 'PVL'
                && $year === 2026
                && $month === 9
                && data_get($value, 'report_metadata.report_number') === 'INF-009'
                && array_key_exists('previous_run', $value['_validation_dependencies']))
            ->andReturn(str_repeat('a', 64));
        $rag->shouldReceive('search')->once()->with('PVL', 2026, 9)->andReturn($fragments);
        $ai = Mockery::mock(PvlAiReportService::class);
        $ai->shouldReceive('modelIdentifier')->once()->andReturn('test:model');
        $ai->shouldReceive('analyze')->once()->with(
            'PVL',
            2026,
            9,
            Mockery::on(fn ($value) => data_get($value, 'report_metadata.report_number') === 'INF-009'),
            $fragments,
        )->andReturn($aiOutput);
        $mapper = Mockery::mock(PvlReportDataMapper::class);
        $mapper->shouldReceive('map')->once()->with($aiOutput, Mockery::type('array'), 'PVL')->andReturn($mapped);
        $validator = Mockery::mock(PvlReportValidationService::class);
        $validator->shouldReceive('validate')->once()->with($mapped, $aiOutput, Mockery::type('array'), null)->andReturn([
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'data' => $mapped,
            'findings' => [],
        ]);

        $run = $this->service($contextService, $rag, $ai, $mapper, $validator)
            ->generate('PVL', 2026, 9, null, ['report_number' => 'INF-009']);

        $this->assertSame(PvlReportRun::LISTO_PARA_GENERAR, $run->status);
        $this->assertSame('F001', data_get($run->validated_data_json, 'pvl.factura'));
        $this->assertSame('test:model', $run->model_used);
        $this->assertSame(str_repeat('a', 64), $run->source_fingerprint);
        $this->assertCount(7, $run->sources_json);
        $this->assertNull($run->error_message);
    }

    public function test_generate_reuses_cached_run_with_same_source_fingerprint(): void
    {
        $fingerprint = str_repeat('b', 64);
        $cached = PvlReportRun::create([
            'report_type' => 'PVL',
            'month' => 9,
            'year' => 2026,
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'validated_data_json' => ['pvl' => ['periodo' => '2026-09']],
            'prompt_version' => 'test-v1',
            'source_fingerprint' => $fingerprint,
        ]);
        $contextService = Mockery::mock(PvlReportContextService::class);
        $contextService->shouldReceive('build')->once()->andReturn(['trazabilidad' => []]);
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldReceive('sourceFingerprint')->once()->andReturn($fingerprint);
        $rag->shouldNotReceive('search');
        $ai = Mockery::mock(PvlAiReportService::class);
        $ai->shouldNotReceive('modelIdentifier');
        $ai->shouldNotReceive('analyze');
        $mapper = Mockery::mock(PvlReportDataMapper::class);
        $mapper->shouldNotReceive('map');
        $validator = Mockery::mock(PvlReportValidationService::class);
        $validator->shouldNotReceive('validate');

        $result = $this->service($contextService, $rag, $ai, $mapper, $validator)
            ->generate('PVL', 2026, 9, null);

        $this->assertTrue($cached->is($result));
        $this->assertTrue($result->getAttribute('was_cached'));
        $this->assertDatabaseCount('pvl_report_runs', 1);
    }

    public function test_rag_failure_is_persisted_as_retryable_report_error(): void
    {
        [$contextService, $rag, $ai, $mapper, $validator] = $this->baseMocks();
        $rag->shouldReceive('search')->once()->andThrow(new RuntimeException('Índice temporalmente no disponible'));

        $run = $this->service($contextService, $rag, $ai, $mapper, $validator)
            ->generate('PVL', 2026, 9, null);

        $this->assertSame(PvlReportRun::ERROR, $run->status);
        $this->assertStringStartsWith('ERROR_RAG:', $run->error_message);
        $this->assertStringContainsString('Índice temporalmente no disponible', $run->error_message);
    }

    public function test_ai_provider_reason_is_preserved_for_http_error_mapping(): void
    {
        [$contextService, $rag, $ai, $mapper, $validator] = $this->baseMocks();
        $rag->shouldReceive('search')->once()->andReturn([]);
        $ai->shouldReceive('analyze')
            ->once()
            ->andThrow(new AiProviderException('Cuota agotada', 429, 'RATE_LIMIT'));

        $run = $this->service($contextService, $rag, $ai, $mapper, $validator)
            ->generate('AMBOS', 2026, 9, null);

        $this->assertSame(PvlReportRun::ERROR, $run->status);
        $this->assertSame('ERROR_AI_RATE_LIMIT: Cuota agotada', $run->error_message);
    }

    public function test_mapping_or_validation_failure_never_marks_report_as_ready(): void
    {
        [$contextService, $rag, $ai, $mapper, $validator] = $this->baseMocks();
        $rag->shouldReceive('search')->once()->andReturn([]);
        $ai->shouldReceive('analyze')->once()->andReturn(['datos' => []]);
        $mapper->shouldReceive('map')->once()->andThrow(new RuntimeException('Salida incompleta'));

        $run = $this->service($contextService, $rag, $ai, $mapper, $validator)
            ->generate('PVL', 2026, 9, null);

        $this->assertSame(PvlReportRun::ERROR, $run->status);
        $this->assertSame('ERROR_VALIDACION: Salida incompleta', $run->error_message);
        $this->assertNull($run->validated_data_json);
    }

    private function baseMocks(): array
    {
        $contextService = Mockery::mock(PvlReportContextService::class);
        $contextService->shouldReceive('build')->once()->andReturn(['trazabilidad' => []]);
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldReceive('sourceFingerprint')->once()->andReturn(hash('sha256', uniqid('fingerprint', true)));
        $ai = Mockery::mock(PvlAiReportService::class);
        $ai->shouldReceive('modelIdentifier')->once()->andReturn('test:model');
        $mapper = Mockery::mock(PvlReportDataMapper::class);
        $validator = Mockery::mock(PvlReportValidationService::class);

        return [$contextService, $rag, $ai, $mapper, $validator];
    }

    private function service(
        PvlReportContextService $contextService,
        PvlRagService $rag,
        PvlAiReportService $ai,
        PvlReportDataMapper $mapper,
        PvlReportValidationService $validator,
    ): PvlReportGeneratorService {
        return new PvlReportGeneratorService(
            $contextService,
            $rag,
            $ai,
            $mapper,
            $validator,
            new PvlReportDefaultsService(),
        );
    }
}
