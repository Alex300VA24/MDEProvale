<?php

namespace Tests\Feature\Api;

use App\Exceptions\AiProviderException;
use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Models\PvlReportRun;
use App\Models\User;
use App\Services\Pvl\PvlRagService;
use App\Services\Pvl\PvlReportGeneratorService;
use App\Services\Pvl\PvlSupportingReportService;
use App\Services\ReportePvlPdfService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class PvlReportsApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/reportes-pvl';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        $this->seedReportsModule();
        Storage::fake('local');
    }

    public function test_index_requires_authentication_and_reports_module_access(): void
    {
        $this->getJson(self::BASE)->assertUnauthorized();

        $this->actingAs($this->userWithoutAccess())
            ->getJson(self::BASE)
            ->assertForbidden();
    }

    public function test_index_returns_runs_documents_products_and_supported_types(): void
    {
        $run = $this->reportRun();
        $document = $this->document(['index_status' => 'INDEXADO']);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE)
            ->assertOk()
            ->assertJsonPath('runs.0.id', $run->id)
            ->assertJsonPath('documents.0.id', $document->id)
            ->assertJsonPath('documents.0.index_status', 'INDEXADO')
            ->assertJsonPath('document_types.0', PvlDocument::TYPES[0])
            ->assertJsonMissingPath('documents.0.file_data')
            ->assertJsonMissingPath('documents.0.file_path');
    }

    public function test_store_document_saves_binary_and_indexes_with_period_traceability(): void
    {
        $binary = "Factura F001\nLeche evaporada\nTotal 120.50";
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (PvlDocument $document, string $received) use ($binary) {
                $this->assertSame($binary, $received);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();
                PvlDocumentChunk::create([
                    'pvl_document_id' => $document->id,
                    'page_number' => 1,
                    'chunk_index' => 1,
                    'content' => 'Factura F001',
                    'embedding' => [1.0, 0.0],
                    'metadata' => ['periodo' => $document->period],
                ]);

                return true;
            });
        $this->app->instance(PvlRagService::class, $rag);

        $response = $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'document_type' => 'factura',
                'period' => '2026-09',
                'provider_reference' => 'OC-2026-009',
                'file' => UploadedFile::fake()->createWithContent('factura.txt', $binary),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('document.document_type', 'factura')
            ->assertJsonPath('document.period', '2026-09')
            ->assertJsonPath('document.provider_reference', 'OC-2026-009')
            ->assertJsonPath('document.index_status', 'INDEXADO');

        $document = PvlDocument::findOrFail($response->json('document.id'));
        $this->assertSame($this->adminUser()->id, $document->created_by);
        $this->assertSame(hash('sha256', $binary), $document->file_hash);
        $this->assertNull($document->file_data);
        $this->assertSame(1, $document->chunks()->count());
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_store_document_validates_type_period_product_and_file(): void
    {
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldNotReceive('indexDocument');
        $this->app->instance(PvlRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'document_type' => 'tipo_inexistente',
                'period' => '09-2026',
                'product_id' => 999999,
                'file' => UploadedFile::fake()->create('archivo.exe', 1, 'application/octet-stream'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_type', 'period', 'product_id', 'file']);

        $this->assertDatabaseCount('pvl_documents', 0);
    }

    public function test_duplicate_is_scoped_to_same_period_type_and_hash(): void
    {
        $binary = 'Factura repetida';
        $existing = $this->document([
            'document_type' => 'factura',
            'period' => '2026-09',
            'file_hash' => hash('sha256', $binary),
        ]);
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldNotReceive('indexDocument');
        $this->app->instance(PvlRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'document_type' => 'factura',
                'period' => '2026-09',
                'file' => UploadedFile::fake()->createWithContent('copia.txt', $binary),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('document.id', $existing->id);

        $this->assertDatabaseCount('pvl_documents', 1);
    }

    public function test_document_indexing_errors_return_actionable_status_and_code(): void
    {
        $rag = Mockery::mock(PvlRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->andThrow(new AiProviderException('Cuota agotada.', 429, 'RATE_LIMIT'));
        $this->app->instance(PvlRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'document_type' => 'factura',
                'period' => '2026-09',
                'file' => UploadedFile::fake()->createWithContent('factura.txt', 'contenido'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'ERROR_AI_RATE_LIMIT')
            ->assertJsonPath('document.file_name', 'factura.txt');

        $this->assertDatabaseCount('pvl_documents', 1);
    }

    public function test_download_and_destroy_manage_binary_and_chunks(): void
    {
        $document = $this->document([
            'file_path' => 'rag/pvl/factura.bin',
            'file_data' => null,
            'index_status' => 'INDEXADO',
        ]);
        Storage::disk('local')->put($document->file_path, 'factura-binaria');
        PvlDocumentChunk::create([
            'pvl_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'fragmento',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('api.reportes-pvl.documents.download', $document))
            ->assertOk()
            ->assertSee('factura-binaria');
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE.'/documents/'.$document->id)
            ->assertOk();

        Storage::disk('local')->assertMissing('rag/pvl/factura.bin');
        $this->assertDatabaseMissing('pvl_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('pvl_document_chunks', ['pvl_document_id' => $document->id]);
    }

    public function test_analyze_validates_payload_and_returns_completed_run(): void
    {
        $run = $this->reportRun([
            'report_type' => 'AMBOS',
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'input_snapshot_json' => ['report_metadata' => ['report_number' => 'INF-001']],
        ]);
        $generator = Mockery::mock(PvlReportGeneratorService::class);
        $generator->shouldReceive('generate')
            ->once()
            ->with('AMBOS', 2026, 9, $this->adminUser()->id, ['report_number' => 'INF-001'])
            ->andReturn($run);
        $this->app->instance(PvlReportGeneratorService::class, $generator);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/analizar', [
                'report_type' => 'INVALIDO',
                'month' => 13,
                'year' => 1999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['report_type', 'month', 'year']);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/analizar', [
                'report_type' => 'AMBOS',
                'month' => 9,
                'year' => 2026,
                'report_metadata' => ['report_number' => 'INF-001'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Análisis completado.')
            ->assertJsonPath('run.id', $run->id)
            ->assertJsonPath('run.can_generate', true)
            ->assertJsonPath('run.report_metadata.report_number', 'INF-001');
    }

    public function test_analyze_translates_ai_rate_limit_into_429_response(): void
    {
        $run = $this->reportRun([
            'status' => PvlReportRun::ERROR,
            'error_message' => 'ERROR_AI_RATE_LIMIT: Límite temporal alcanzado.',
        ]);
        $generator = Mockery::mock(PvlReportGeneratorService::class);
        $generator->shouldReceive('generate')->once()->andReturn($run);
        $this->app->instance(PvlReportGeneratorService::class, $generator);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/analizar', [
                'report_type' => 'PVL',
                'month' => 9,
                'year' => 2026,
            ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'ERROR_AI_RATE_LIMIT')
            ->assertJsonPath('message', 'Límite temporal alcanzado.');
    }

    public function test_show_hides_internal_finding_signature_and_destroy_removes_run(): void
    {
        $run = $this->reportRun([
            'warnings_json' => [[
                'nivel' => 'ADVERTENCIA',
                'mensaje' => 'Revisar saldo',
                '_signature' => 'dato-interno',
            ]],
        ]);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE.'/runs/'.$run->id)
            ->assertOk()
            ->assertJsonPath('run.findings.0.mensaje', 'Revisar saldo')
            ->assertJsonMissing(['_signature' => 'dato-interno']);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE.'/runs/'.$run->id)
            ->assertOk();

        $this->assertDatabaseMissing('pvl_report_runs', ['id' => $run->id]);
    }

    public function test_mark_generated_rejects_run_with_critical_findings(): void
    {
        $run = $this->reportRun(['status' => PvlReportRun::REQUIERE_REVISION]);
        $pdfService = Mockery::mock(ReportePvlPdfService::class);
        $pdfService->shouldNotReceive('forType');
        $this->app->instance(ReportePvlPdfService::class, $pdfService);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/runs/'.$run->id.'/generar')
            ->assertUnprocessable();

        $this->assertSame(PvlReportRun::REQUIERE_REVISION, $run->fresh()->status);
        $this->assertNull($run->fresh()->generated_at);
    }

    public function test_mark_generated_builds_both_annexes_and_supporting_report(): void
    {
        $run = $this->reportRun([
            'report_type' => 'AMBOS',
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'validated_data_json' => [
                'pvl' => ['periodo' => '2026-09'],
                'racion_a' => ['periodo' => '2026-09'],
            ],
        ]);
        $supportingData = ['informe' => 'sustento'];
        $supporting = Mockery::mock(PvlSupportingReportService::class);
        $supporting->shouldReceive('build')->once()->with(Mockery::on(fn ($value) => $value->is($run)))->andReturn($supportingData);
        $this->app->instance(PvlSupportingReportService::class, $supporting);

        $pdf = Mockery::mock(PDF::class);
        $pdf->shouldReceive('output')->times(3)->andReturn('%PDF-1.4');
        $pdfService = Mockery::mock(ReportePvlPdfService::class);
        $pdfService->shouldReceive('forType')->once()->with('pvl', ['periodo' => '2026-09'])->andReturn($pdf);
        $pdfService->shouldReceive('forType')->once()->with('racion-a', ['periodo' => '2026-09'])->andReturn($pdf);
        $pdfService->shouldReceive('forType')->once()->with('informe', $supportingData)->andReturn($pdf);
        $this->app->instance(ReportePvlPdfService::class, $pdfService);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/runs/'.$run->id.'/generar')
            ->assertOk()
            ->assertJsonPath('run.status', PvlReportRun::GENERADO)
            ->assertJsonCount(3, 'files')
            ->assertJsonPath('files.0.type', 'pvl')
            ->assertJsonPath('files.1.type', 'racion-a')
            ->assertJsonPath('files.2.type', 'informe');

        $this->assertNotNull($run->fresh()->generated_at);
        $this->assertNull($run->fresh()->error_message);
    }

    public function test_mark_generated_detects_missing_validated_data(): void
    {
        $run = $this->reportRun([
            'report_type' => 'PVL',
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'validated_data_json' => [],
        ]);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/runs/'.$run->id.'/generar')
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'ERROR_DATOS');

        $this->assertSame('ERROR_DATOS', $run->fresh()->error_message);
        $this->assertSame(PvlReportRun::LISTO_PARA_GENERAR, $run->fresh()->status);
    }

    public function test_mark_generated_reports_pdf_failure_without_marking_run_as_generated(): void
    {
        $run = $this->reportRun([
            'report_type' => 'PVL',
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'validated_data_json' => ['pvl' => ['periodo' => '2026-09']],
        ]);
        $pdfService = Mockery::mock(ReportePvlPdfService::class);
        $pdfService->shouldReceive('forType')->once()->andThrow(new RuntimeException('Fallo de render'));
        $this->app->instance(ReportePvlPdfService::class, $pdfService);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/runs/'.$run->id.'/generar')
            ->assertInternalServerError()
            ->assertJsonPath('error_code', 'ERROR_PDF');

        $this->assertSame('ERROR_PDF', $run->fresh()->error_message);
        $this->assertSame(PvlReportRun::LISTO_PARA_GENERAR, $run->fresh()->status);
        $this->assertNull($run->fresh()->generated_at);
    }

    private function seedReportsModule(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Reportes',
            'slug' => 'reportes',
            'description' => 'Informes PVL',
            'icon' => 'fa-file-pdf',
            'route' => 'reportes',
            'order' => 9,
            'is_active' => true,
        ]);

        DB::table('module_rol')->insert([
            'module_id' => $moduleId,
            'rol_id' => 1,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);
    }

    private function userWithoutAccess(): User
    {
        $rolId = DB::table('rols')->insertGetId([
            'title' => 'Sin reportes',
            'description' => null,
        ]);

        return User::create([
            'names' => 'Usuario',
            'father_surname' => 'Sin',
            'mother_surname' => 'Reportes',
            'username' => 'sin-reportes',
            'email' => 'sin-reportes@example.com',
            'dni' => '00000992',
            'cui' => '0',
            'state_id' => 1,
            'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);
    }

    private function document(array $overrides = []): PvlDocument
    {
        return PvlDocument::create(array_merge([
            'document_type' => 'factura',
            'period' => '2026-09',
            'file_name' => 'factura.txt',
            'mime_type' => 'text/plain',
            'file_size' => 20,
            'file_hash' => hash('sha256', uniqid('pvl-doc', true)),
            'file_data' => base64_encode('legacy'),
            'index_status' => 'PENDIENTE',
            'created_by' => $this->adminUser()->id,
        ], $overrides));
    }

    private function reportRun(array $overrides = []): PvlReportRun
    {
        return PvlReportRun::create(array_merge([
            'report_type' => 'PVL',
            'month' => 9,
            'year' => 2026,
            'status' => PvlReportRun::LISTO_PARA_GENERAR,
            'input_snapshot_json' => [],
            'ai_output_json' => [],
            'validated_data_json' => ['pvl' => ['periodo' => '2026-09']],
            'warnings_json' => [],
            'sources_json' => [],
            'model_used' => 'test:model',
            'prompt_version' => 'test-v1',
            'source_fingerprint' => hash('sha256', uniqid('pvl-run', true)),
            'created_by' => $this->adminUser()->id,
        ], $overrides));
    }
}
