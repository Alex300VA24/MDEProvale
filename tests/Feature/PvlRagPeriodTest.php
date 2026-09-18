<?php

namespace Tests\Feature;

use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Services\AssistantAiService;
use App\Services\Pvl\PvlRagService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PvlRagPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_never_mixes_documents_from_another_period(): void
    {
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->andReturn(null);
        $service = new PvlRagService($ai);

        $june = $this->document('2026-06', 'junio.txt');
        $july = $this->document('2026-07', 'julio.txt');
        PvlDocumentChunk::create(['pvl_document_id' => $june->id, 'page_number' => 1, 'chunk_index' => 1, 'content' => 'Factura de leche de junio', 'metadata' => []]);
        PvlDocumentChunk::create(['pvl_document_id' => $july->id, 'page_number' => 1, 'chunk_index' => 1, 'content' => 'Factura de leche de julio', 'metadata' => []]);

        $results = $service->search('PVL', 2026, 6);

        $this->assertCount(1, $results);
        $this->assertSame($june->id, $results[0]['metadata']['document_id']);
        $this->assertSame('2026-06', $results[0]['metadata']['periodo']);
    }

    public function test_it_extracts_text_from_a_pdf_locally_when_groq_is_selected(): void
    {
        config()->set('services.ai.provider', 'groq');
        $binary = Pdf::loadHtml('<p>CONSTANCIA DE ENVIO CODIGO 000123</p>')->output();
        $document = PvlDocument::create([
            'document_type' => 'constancia_envio',
            'period' => '2026-06',
            'file_name' => 'constancia.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($binary),
            'file_hash' => hash('sha256', $binary),
            'file_data' => $binary,
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->andReturn(null);
        $ai->shouldNotReceive('extractDocumentText');

        (new PvlRagService($ai))->indexDocument($document, $binary);

        $this->assertSame('INDEXADO', $document->fresh()->index_status);
        $this->assertStringContainsString(
            'CONSTANCIA DE ENVIO CODIGO 000123',
            (string) $document->chunks()->value('content'),
        );
    }

    private function document(string $period, string $name): PvlDocument
    {
        return PvlDocument::create([
            'document_type' => 'factura', 'period' => $period, 'file_name' => $name,
            'mime_type' => 'text/plain', 'file_size' => 4, 'file_hash' => hash('sha256', $name),
            'file_data' => 'text', 'index_status' => 'INDEXADO',
        ]);
    }
}
