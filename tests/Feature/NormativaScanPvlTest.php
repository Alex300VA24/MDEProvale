<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NormativaDocument;
use App\Exceptions\AiProviderException;
use App\Services\AssistantAiService;
use App\Services\Normativa\NormativaRagService;
use App\Services\Normativa\NormativaScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class NormativaScanPvlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_it_discovers_classifies_and_notifies_relevant_documents(): void
    {
        Http::fake([
            '*normativa.php*' => Http::response($this->fixtureHtml(), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 contenido de prueba', 200),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')
            ->once()
            ->andReturn(['relevante' => true, 'resumen' => 'Resumen de prueba', 'motivo' => 'Menciona el comité de vaso de leche']);

        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')->once();

        $result = (new NormativaScraperService($ai, $rag))->scanAndNotify();

        $this->assertSame(2, $result['descubiertos']);
        $this->assertSame(1, $result['clasificados']);
        $this->assertSame(1, $result['relevantes']);
        $this->assertSame(2, NormativaDocument::count());
        $this->assertSame(1, NormativaDocument::where('relevancia_pvl', true)->count());
        $this->assertSame(1, Notification::where('type', 'normativa_pvl')->count());
    }

    public function test_it_does_not_call_ai_for_documents_without_pvl_keywords(): void
    {
        Http::fake(['*normativa.php*' => Http::response($this->fixtureHtml(onlyIrrelevant: true), 200)]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');

        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldNotReceive('indexDocument');

        $result = (new NormativaScraperService($ai, $rag))->scanAndNotify();

        $this->assertSame(1, $result['descubiertos']);
        $this->assertSame(0, $result['clasificados']);
        $this->assertSame('NO_RELEVANTE', NormativaDocument::first()->index_status);
        $this->assertSame(0, Notification::where('type', 'normativa_pvl')->count());
    }

    public function test_scanning_twice_does_not_duplicate_documents_or_notifications(): void
    {
        Http::fake([
            '*normativa.php*' => Http::response($this->fixtureHtml(), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 contenido de prueba', 200),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')
            ->once()
            ->andReturn(['relevante' => true, 'resumen' => 'Resumen', 'motivo' => 'Motivo']);

        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')->once();

        $service = new NormativaScraperService($ai, $rag);
        $service->scanAndNotify();
        $second = $service->scanAndNotify();

        $this->assertSame(0, $second['descubiertos']);
        $this->assertSame(2, NormativaDocument::count());
        $this->assertSame(1, Notification::where('type', 'normativa_pvl')->count());
    }

    public function test_manual_import_includes_historical_matches_and_does_not_duplicate_them(): void
    {
        config([
            'normativa.tipos' => [2 => 'RESOLUCION DE ALCALDIA'],
            'normativa.backfill_days' => 1,
            'normativa.request_delay_ms' => 0,
        ]);

        Http::fake([
            '*normativa.php*' => Http::response($this->fixtureHtml(date: '2020-01-15'), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 contenido histórico', 200),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')
            ->once()
            ->andReturn(['relevante' => true, 'resumen' => 'Norma histórica PVL', 'motivo' => 'Regula el comité de vaso de leche']);

        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (NormativaDocument $document, string $binary) {
                $this->assertSame('%PDF-1.4 contenido histórico', $binary);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });

        $service = new NormativaScraperService($ai, $rag);
        $first = $service->importRelevantDocuments();
        $second = $service->importRelevantDocuments();

        $this->assertSame(1, $first['importados']);
        $this->assertSame(1, $second['ya_importados']);
        $this->assertSame('2020-01-15', NormativaDocument::where('relevancia_pvl', true)->firstOrFail()->fecha_documento->format('Y-m-d'));
        $this->assertSame(1, Notification::where('type', 'normativa_pvl')->count());
    }

    public function test_it_imports_a_verified_copy_link_and_downloads_the_real_pdf_from_its_viewer(): void
    {
        config(['normativa.request_delay_ms' => 0]);
        $pdf = '%PDF-1.4 copia verificable real';

        Http::fake([
            '*norma_verificable_cache.php*' => Http::response($pdf, 200, ['Content-Type' => 'application/pdf']),
            '*norma_descargar.php*' => Http::response($this->verificationHtml(), 200, ['Content-Type' => 'text/html']),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');

        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (NormativaDocument $document, string $binary) use ($pdf) {
                $this->assertSame(35639, $document->external_id);
                $this->assertSame('0750-2026-MDE', $document->numero);
                $this->assertSame($pdf, $binary);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });

        $result = (new NormativaScraperService($ai, $rag))->importDocument(
            'https://www.muniesperanza.gob.pe/website/mde2026/norma_descargar.php?id=35639'
        );

        $this->assertTrue($result['importado']);
        $this->assertSame('0750-2026-MDE', $result['numero']);
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            'norma_descargar.php?id=35639'
        ));
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            'norma_verificable_cache.php?c=MDE-NOR-2026-CAB466A6'
        ));
        $this->assertDatabaseHas('normativa_documents', [
            'external_id' => 35639,
            'relevancia_pvl' => true,
            'index_status' => 'INDEXADO',
        ]);
        $document = NormativaDocument::where('external_id', 35639)->firstOrFail();
        $this->assertSame(strlen($pdf), $document->file_size);
        $this->assertSame(hash('sha256', $pdf), $document->file_hash);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_bulk_import_directly_includes_requested_club_and_quinoa_terms(): void
    {
        config([
            'normativa.tipos' => [2 => 'RESOLUCION DE ALCALDIA'],
            'normativa.request_delay_ms' => 0,
        ]);
        $pdf = '%PDF-1.4 norma sobre alimentos fortificados';

        Http::fake([
            '*normativa.php*' => Http::response($this->requestedTermsFixtureHtml(), 200),
            '*norma_descargar.php*' => Http::response($pdf, 200, ['Content-Type' => 'application/pdf']),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');
        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (NormativaDocument $document, string $binary) use ($pdf) {
                $this->assertSame($pdf, $binary);
                $this->assertTrue($document->relevancia_pvl);
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });

        $result = (new NormativaScraperService($ai, $rag))->importRelevantDocuments();

        $this->assertSame(1, $result['encontrados']);
        $this->assertSame(1, $result['importados']);
        $document = NormativaDocument::firstOrFail();
        $this->assertStringContainsString('club de madres', mb_strtolower($document->relevancia_motivo));
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_bulk_import_directly_includes_the_requested_fortified_product_phrase(): void
    {
        config([
            'normativa.tipos' => [2 => 'RESOLUCION DE ALCALDIA'],
            'normativa.request_delay_ms' => 0,
        ]);

        Http::fake([
            '*normativa.php*' => Http::response($this->requestedTermsFixtureHtml(false), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 producto alimentario', 200),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');
        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (NormativaDocument $document) {
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });

        $result = (new NormativaScraperService($ai, $rag))->importRelevantDocuments();

        $this->assertSame(1, $result['importados']);
        $this->assertStringContainsString(
            'hojuela de quinua',
            NormativaDocument::firstOrFail()->relevancia_motivo,
        );
    }

    public function test_bulk_import_downloads_a_local_copy_for_an_already_indexed_norm(): void
    {
        config([
            'normativa.tipos' => [2 => 'RESOLUCION DE ALCALDIA'],
            'normativa.request_delay_ms' => 0,
        ]);
        NormativaDocument::create([
            'external_id' => 90001,
            'tipo_id' => 2,
            'tipo_documento' => 'RESOLUCION DE ALCALDIA',
            'periodo' => '2026-10',
            'numero' => '0001-2026-MDE',
            'titulo' => 'RESOLUCION DE ALCALDIA N°0001-2026-MDE',
            'asunto' => 'APROBAR la conformación del Comité de Vaso de Leche del distrito.',
            'fecha_documento' => '2026-10-05',
            'pdf_url' => 'https://www.muniesperanza.gob.pe/website/mde2026/norma_descargar.php?id=90001',
            'relevancia_pvl' => true,
            'index_status' => 'INDEXADO',
        ]);

        Http::fake([
            '*normativa.php*' => Http::response($this->fixtureHtml(date: '2026-10-05'), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 copia local recuperada', 200),
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');
        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldNotReceive('indexDocument');

        $result = (new NormativaScraperService($ai, $rag))->importRelevantDocuments();

        $this->assertSame(1, $result['ya_importados']);
        $document = NormativaDocument::where('external_id', 90001)->firstOrFail();
        $this->assertSame('INDEXADO', $document->index_status);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_it_finds_and_imports_a_document_by_resolution_number(): void
    {
        config(['normativa.request_delay_ms' => 0]);
        Http::fake([
            '*normativa.php*' => Http::response($this->fixtureHtml(onlyIrrelevant: false, date: '17/08/2026'), 200),
            '*norma_descargar.php*' => Http::response('%PDF-1.4 resolución encontrada', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldNotReceive('generateStructured');
        $rag = Mockery::mock(NormativaRagService::class);
        $rag->shouldReceive('indexDocument')
            ->once()
            ->withArgs(function (NormativaDocument $document) {
                $document->forceFill(['index_status' => 'INDEXADO'])->save();

                return true;
            });

        $result = (new NormativaScraperService($ai, $rag))->importDocument('0001-2026-MDE');

        $this->assertTrue($result['importado']);
        $this->assertSame('2026-08-17', NormativaDocument::findOrFail($result['document_id'])->fecha_documento->format('Y-m-d'));
    }

    public function test_it_rejects_verified_copy_links_from_other_hosts(): void
    {
        Http::preventStrayRequests();
        $ai = Mockery::mock(AssistantAiService::class);
        $rag = Mockery::mock(NormativaRagService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('portal oficial');

        (new NormativaScraperService($ai, $rag))->importDocument(
            'https://example.com/website/mde2026/norma_descargar.php?id=35639'
        );
    }

    public function test_normativa_rag_uses_official_metadata_when_gemini_ocr_is_rejected(): void
    {
        config(['rag.embedding_dim' => 0]);
        $document = NormativaDocument::create([
            'external_id' => 99901,
            'tipo_id' => 2,
            'tipo_documento' => 'RESOLUCION DE ALCALDIA',
            'periodo' => '2026-08',
            'numero' => '0750-2026-MDE',
            'titulo' => 'RESOLUCION DE ALCALDIA N°0750-2026-MDE',
            'asunto' => 'Conforma el Comité de Administración del Programa de Vaso de Leche.',
            'concepto' => 'Integración y funciones del comité del PVL.',
            'fecha_documento' => '2026-08-17',
            'pdf_url' => 'https://www.muniesperanza.gob.pe/website/mde2026/norma_descargar.php?id=99901',
            'relevancia_pvl' => true,
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('extractDocumentText')
            ->once()
            ->andThrow(new AiProviderException('Request contains an invalid argument.', 502, 'REQUEST_REJECTED'));
        $ai->shouldReceive('provider')->once()->andReturn('google');
        $ai->shouldReceive('embedText')->once()->andReturn(null);

        (new NormativaRagService($ai))->indexDocument($document, 'PDF escaneado sin capa de texto');

        $document->refresh();
        $this->assertSame('INDEXADO', $document->index_status);
        $this->assertStringContainsString('Programa de Vaso de Leche', $document->chunks()->firstOrFail()->content);
        $this->assertSame('portal_metadata', $document->chunks()->firstOrFail()->metadata['text_source']);
    }

    private function fixtureHtml(bool $onlyIrrelevant = false, ?string $date = null): string
    {
        $today = $date ?? now()->format('Y-m-d');

        $relevant = <<<HTML
          <article class="mde-doc">
            <div class="mde-doc__body">
              <small>RESOLUCIONES DE ALCALDÍA · 2026 · Septiembre</small>
              <strong>RESOLUCION DE ALCALDIA N°0001-2026-MDE</strong>
              <div class="mde-norma-subject"><span>Asunto</span><p>APROBAR la conformación del Comité de Vaso de Leche del distrito.</p></div>
              <div class="mde-norma-concept"><span>Concepto</span><p>APROBAR la conformación del Comité de Vaso de Leche.</p></div>
              <span>{$today}</span>
            </div>
            <div class="mde-doc__buttons">
              <a href="/website/mde2026/norma_descargar.php?id=90001">Copia verificable</a>
            </div>
          </article>
        HTML;

        $irrelevant = <<<HTML
          <article class="mde-doc">
            <div class="mde-doc__body">
              <small>RESOLUCIONES DE ALCALDÍA · 2026 · Septiembre</small>
              <strong>RESOLUCION DE ALCALDIA N°0002-2026-MDE</strong>
              <div class="mde-norma-subject"><span>Asunto</span><p>DESIGNAR al Subgerente de Fiscalización de la Municipalidad.</p></div>
              <div class="mde-norma-concept"><span>Concepto</span><p>DESIGNAR al Subgerente de Fiscalización.</p></div>
              <span>{$today}</span>
            </div>
            <div class="mde-doc__buttons">
              <a href="/website/mde2026/norma_descargar.php?id=90002">Copia verificable</a>
            </div>
          </article>
        HTML;

        $articles = $onlyIrrelevant ? $irrelevant : $relevant.$irrelevant;

        return '<div class="mde-doc-list mde-doc-list--normativa">'.$articles.'</div>';
    }

    private function verificationHtml(): string
    {
        $data = json_encode([
            'id' => 35639,
            'codigo' => 'MDE-NOR-2026-CAB466A6',
            'titulo' => 'RESOLUCION DE ALCALDIA N°0750-2026-MDE',
            'asunto' => 'Conforma un Comité de Administración del Programa de Vaso de Leche.',
            'tipo' => 'RESOLUCIONES DE ALCALDÍA',
            'fecha' => '17/08/2026',
            'archivo_url' => 'norma_archivo.php?id=35639',
            'cache_ready' => true,
            'cache_url' => 'norma_verificable_cache.php?c=MDE-NOR-2026-CAB466A6',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<html><script>window.MDE_VERIFIED_PDF = '.$data.';</script></html>';
    }

    private function requestedTermsFixtureHtml(bool $includeClub = true): string
    {
        $subject = $includeClub
            ? 'Reconocer al CLUB DE MADRES Nueva Esperanza.'
            : 'Aprobar las especificaciones del producto alimentario para el programa social.';

        return <<<HTML
          <div class="mde-doc-list mde-doc-list--normativa">
            <article class="mde-doc">
              <div class="mde-doc__body">
                <small>RESOLUCIONES DE ALCALDÍA · 2026 · Octubre</small>
                <strong>RESOLUCION DE ALCALDIA N°0900-2026-MDE</strong>
                <div class="mde-norma-subject"><span>Asunto</span><p>{$subject}</p></div>
                <div class="mde-norma-concept"><span>Concepto</span><p>HOJUELA DE QUINUA AVENA CON AZÚCAR FORTIFICADA CON VITAMINAS Y MINERALES.</p></div>
                <span>2026-10-05</span>
              </div>
              <div class="mde-doc__buttons">
                <a href="/website/mde2026/norma_descargar.php?id=90900">Copia verificable</a>
              </div>
            </article>
          </div>
        HTML;
    }
}
