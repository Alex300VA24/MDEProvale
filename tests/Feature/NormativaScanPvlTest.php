<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NormativaDocument;
use App\Services\AssistantAiService;
use App\Services\Normativa\NormativaRagService;
use App\Services\Normativa\NormativaScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class NormativaScanPvlTest extends TestCase
{
    use RefreshDatabase;

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

    private function fixtureHtml(bool $onlyIrrelevant = false): string
    {
        $today = now()->format('Y-m-d');

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
}
