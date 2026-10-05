<?php

namespace Tests\Feature\Api;

use App\Exceptions\AiProviderException;
use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Models\User;
use App\Services\KnowledgeBase\KnowledgeBaseRagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class KnowledgeBaseApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/base-conocimiento';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        $this->seedModule();
        Storage::fake('local');
    }

    public function test_index_requires_authentication_and_module_access(): void
    {
        $this->getJson(self::BASE)->assertUnauthorized();

        $this->actingAs($this->userWithoutAccess())
            ->getJson(self::BASE)
            ->assertForbidden();
    }

    public function test_index_returns_documents_with_safe_metadata_and_download_url(): void
    {
        $document = $this->document([
            'title' => 'Manual operativo',
            'index_status' => 'INDEXADO',
        ]);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE)
            ->assertOk()
            ->assertJsonCount(1, 'documents')
            ->assertJsonPath('documents.0.id', $document->id)
            ->assertJsonPath('documents.0.title', 'Manual operativo')
            ->assertJsonPath('documents.0.index_status', 'INDEXADO')
            ->assertJsonPath(
                'documents.0.download_url',
                route('api.base-conocimiento.documents.download', $document),
            )
            ->assertJsonMissingPath('documents.0.file_data')
            ->assertJsonMissingPath('documents.0.file_path');
    }

    public function test_store_saves_binary_indexes_document_and_records_creator(): void
    {
        $binary = '%PDF-1.4 manual de procedimientos';
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (KnowledgeBaseDocument $document, string $received) use ($binary) {
                $this->assertSame($binary, $received);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();
                KnowledgeBaseDocumentChunk::create([
                    'kb_document_id' => $document->id,
                    'page_number' => 1,
                    'chunk_index' => 1,
                    'content' => 'Manual de procedimientos',
                    'embedding' => [1.0, 0.0],
                    'metadata' => ['pagina' => 1],
                ]);

                return true;
            });
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $response = $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'title' => '  Manual institucional  ',
                'file' => UploadedFile::fake()->createWithContent('manual.pdf', $binary),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('document.title', 'Manual institucional')
            ->assertJsonPath('document.index_status', 'INDEXADO');

        $document = KnowledgeBaseDocument::findOrFail($response->json('document.id'));
        $this->assertSame($this->adminUser()->id, $document->created_by);
        $this->assertSame(hash('sha256', $binary), $document->file_hash);
        $this->assertNull($document->file_data);
        $this->assertSame(1, $document->chunks()->count());
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertSame($binary, Storage::disk('local')->get($document->file_path));
    }

    public function test_store_rejects_invalid_files_before_indexing(): void
    {
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldNotReceive('indexDocument');
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'title' => str_repeat('x', 256),
                'file' => UploadedFile::fake()->create('malware.exe', 1, 'application/octet-stream'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'file']);

        $this->assertDatabaseCount('kb_documents', 0);
    }

    public function test_store_rejects_duplicate_binary_without_reindexing(): void
    {
        $binary = '%PDF-1.4 contenido repetido';
        $existing = $this->document([
            'file_hash' => hash('sha256', $binary),
            'index_status' => 'INDEXADO',
        ]);
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldNotReceive('indexDocument');
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'file' => UploadedFile::fake()->createWithContent('copia.pdf', $binary),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('document.id', $existing->id);

        $this->assertDatabaseCount('kb_documents', 1);
    }

    public function test_store_retries_a_duplicate_document_that_previously_failed(): void
    {
        $binary = '%PDF-1.4 acta escaneada';
        $existing = $this->document([
            'file_hash' => hash('sha256', $binary),
            'index_status' => 'ERROR',
            'index_error' => 'No contenía texto seleccionable.',
        ]);
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (KnowledgeBaseDocument $document, string $received) use ($existing, $binary) {
                $this->assertSame($existing->id, $document->id);
                $this->assertSame($binary, $received);
                $document->forceFill(['index_status' => 'INDEXADO', 'index_error' => null])->save();

                return true;
            });
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'file' => UploadedFile::fake()->createWithContent('acta-escaneada.pdf', $binary),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('document.id', $existing->id)
            ->assertJsonPath('document.index_status', 'INDEXADO')
            ->assertJsonFragment(['message' => 'Documento reindexado correctamente con OCR.']);

        $this->assertDatabaseCount('kb_documents', 1);
    }

    public function test_store_can_force_ocr_reindexing_for_an_existing_document(): void
    {
        $binary = '%PDF-1.4 indice anterior incompleto';
        $existing = $this->document([
            'file_hash' => hash('sha256', $binary),
            'index_status' => 'INDEXADO',
        ]);
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (KnowledgeBaseDocument $document, string $received) use ($existing, $binary) {
                $this->assertSame($existing->id, $document->id);
                $this->assertSame($binary, $received);
                $document->forceFill(['index_status' => 'INDEXADO', 'index_error' => null])->save();

                return true;
            });
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'reindex' => true,
                'file' => UploadedFile::fake()->createWithContent('documento.pdf', $binary),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('document.id', $existing->id)
            ->assertJsonPath('document.index_status', 'INDEXADO');

        $this->assertDatabaseCount('kb_documents', 1);
    }

    public function test_store_accepts_a_scanned_png_image(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (KnowledgeBaseDocument $document, string $received) use ($png) {
                $this->assertSame('image/png', $document->mime_type);
                $this->assertSame($png, $received);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'file' => UploadedFile::fake()->createWithContent('escaneo.png', $png),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('document.mime_type', 'image/png')
            ->assertJsonPath('document.index_status', 'INDEXADO');
    }

    public function test_store_reports_ai_indexing_error_and_keeps_document_for_retry(): void
    {
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->andThrow(new AiProviderException('Cuota agotada.', 429, 'RATE_LIMIT'));
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->post(self::BASE.'/documents', [
                'file' => UploadedFile::fake()->createWithContent('manual.pdf', '%PDF-1.4 cuota'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(429)
            ->assertJsonPath('document.file_name', 'manual.pdf')
            ->assertJsonFragment(['message' => 'Archivo guardado, pero no pudo indexarse. Cuota agotada.']);

        $this->assertDatabaseCount('kb_documents', 1);
    }

    public function test_download_returns_stored_binary_and_missing_binary_is_404(): void
    {
        $document = $this->document([
            'file_path' => 'rag/kb/manual.bin',
            'file_data' => null,
        ]);
        Storage::disk('local')->put($document->file_path, 'contenido-binario');

        $response = $this->actingAs($this->adminUser())
            ->get(route('api.base-conocimiento.documents.download', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('contenido-binario');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        Storage::disk('local')->delete($document->file_path);

        $this->actingAs($this->adminUser())
            ->get(route('api.base-conocimiento.documents.download', $document))
            ->assertNotFound();
    }

    public function test_destroy_deletes_binary_document_and_chunks(): void
    {
        $document = $this->document(['file_path' => 'rag/kb/eliminar.bin']);
        Storage::disk('local')->put($document->file_path, 'contenido');
        KnowledgeBaseDocumentChunk::create([
            'kb_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'fragmento',
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE.'/documents/'.$document->id)
            ->assertOk();

        Storage::disk('local')->assertMissing('rag/kb/eliminar.bin');
        $this->assertDatabaseMissing('kb_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('kb_document_chunks', ['kb_document_id' => $document->id]);
    }

    public function test_ask_validates_question_and_returns_grounded_answer(): void
    {
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('answer')
            ->once()
            ->with('¿Cuál es el plazo?')
            ->andReturn([
                'answer' => 'El plazo es de diez días [Fuente 1].',
                'sources' => [['archivo' => 'manual.pdf', 'pagina' => 2]],
            ]);
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/preguntar', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/preguntar', ['question' => '¿Cuál es el plazo?'])
            ->assertOk()
            ->assertJsonPath('answer', 'El plazo es de diez días [Fuente 1].')
            ->assertJsonPath('sources.0.archivo', 'manual.pdf');
    }

    public function test_ask_preserves_ai_provider_http_status(): void
    {
        $rag = Mockery::mock(KnowledgeBaseRagService::class);
        $rag->shouldReceive('answer')
            ->once()
            ->andThrow(new AiProviderException('Proveedor no disponible.', 503, 'UNAVAILABLE'));
        $this->app->instance(KnowledgeBaseRagService::class, $rag);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE.'/preguntar', ['question' => 'Consulta'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Proveedor no disponible.');
    }

    private function seedModule(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'base-conocimiento')->value('id');

        DB::table('module_rol')->updateOrInsert([
            'module_id' => $moduleId,
            'rol_id' => 1,
        ], [
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);
    }

    private function userWithoutAccess(): User
    {
        $rolId = DB::table('rols')->insertGetId([
            'title' => 'Sin base de conocimiento',
            'description' => null,
        ]);

        return User::create([
            'names' => 'Usuario',
            'father_surname' => 'Sin',
            'mother_surname' => 'Acceso',
            'username' => 'sin-kb',
            'email' => 'sin-kb@example.com',
            'dni' => '00000991',
            'cui' => '0',
            'state_id' => 1,
            'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);
    }

    private function document(array $overrides = []): KnowledgeBaseDocument
    {
        return KnowledgeBaseDocument::create(array_merge([
            'title' => 'Documento de prueba',
            'file_name' => 'documento.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 20,
            'file_hash' => hash('sha256', uniqid('kb', true)),
            'file_data' => base64_encode('legacy'),
            'index_status' => 'PENDIENTE',
            'created_by' => $this->adminUser()->id,
        ], $overrides));
    }
}
