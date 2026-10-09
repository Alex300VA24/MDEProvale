<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Services\AssistantAiService;
use App\Services\KnowledgeBase\KnowledgeBaseRagService;
use App\Services\Normativa\NormativaRagService;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Tests\TestCase;
use ZipArchive;

class KnowledgeBaseRagServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_extracts_docx_chunks_and_stores_normalized_embeddings(): void
    {
        config()->set('rag.embedding_dim', 2);
        config()->set('rag.embedding_model', 'embedding-test');
        $document = $this->document([
            'file_name' => 'manual.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        KnowledgeBaseDocumentChunk::create([
            'kb_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'fragmento anterior',
        ]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')
            ->once()
            ->with(Mockery::on(fn (string $text) => str_contains($text, 'procedimiento de entrega')), 'RETRIEVAL_DOCUMENT')
            ->andReturn([3, 4]);

        (new KnowledgeBaseRagService($ai))->indexDocument(
            $document,
            $this->docx('Manual del procedimiento de entrega de alimentos.'),
        );

        $chunk = $document->fresh()->chunks()->sole();
        $this->assertSame('INDEXADO', $document->fresh()->index_status);
        $this->assertStringContainsString('procedimiento de entrega', $chunk->content);
        $this->assertEqualsWithDelta(0.6, $chunk->embedding[0], 0.000001);
        $this->assertEqualsWithDelta(0.8, $chunk->embedding[1], 0.000001);
        $this->assertSame('embedding-test', $chunk->metadata['embedding_model']);
        $this->assertTrue($chunk->metadata['has_embedding']);
    }

    public function test_it_extracts_and_indexes_an_xlsx_workbook(): void
    {
        $document = $this->document([
            'file_name' => 'padron.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')
            ->once()
            ->with(Mockery::on(fn (string $text) => str_contains($text, 'María Quispe')), 'RETRIEVAL_DOCUMENT')
            ->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('groq');

        (new KnowledgeBaseRagService($ai))->indexDocument($document, $this->xlsx());

        $chunk = $document->fresh()->chunks()->sole();
        $this->assertSame('INDEXADO', $document->fresh()->index_status);
        $this->assertStringContainsString('[[HOJA:Beneficiarios]]', $chunk->content);
        $this->assertStringContainsString('María Quispe', $chunk->content);
        $this->assertNull($chunk->embedding);
    }

    public function test_it_uses_gemini_ocr_for_a_scanned_pdf_without_a_text_layer(): void
    {
        $document = $this->document([
            'file_name' => 'acta-escaneada.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('extractDocumentText')
            ->once()
            ->with('application/pdf', 'pdf-escaneado')
            ->andReturn("[[PAGINA:1]]\nACTA N. 001-2026\nPresidenta: María Quispe");
        $ai->shouldReceive('embedText')
            ->once()
            ->with(Mockery::on(fn (string $text) => str_contains($text, 'María Quispe')), 'RETRIEVAL_DOCUMENT')
            ->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('google');

        (new KnowledgeBaseRagService($ai))->indexDocument($document, 'pdf-escaneado');

        $chunk = $document->fresh()->chunks()->sole();
        $this->assertSame('INDEXADO', $document->fresh()->index_status);
        $this->assertSame(1, $chunk->page_number);
        $this->assertStringContainsString('ACTA N. 001-2026', $chunk->content);
        $this->assertStringContainsString('María Quispe', $chunk->content);
    }

    public function test_it_uses_gemini_ocr_when_the_text_layer_only_contains_the_verification_qr(): void
    {
        $verificationText = implode("\n", [
            'COPIA VERIFICABLE',
            'QR DE VERIFICACION',
            'MDE-NOR-2026-312FE938',
            'MDE DOCUMENTO VERIFICABLE MDE-NOR-2026-312FE938',
        ]);
        $pdf = $this->pdf($verificationText);
        $nativeText = trim((new Parser())->parseContent($pdf)->getPages()[0]->getText());
        preg_match_all('/[\pL\pN]/u', $nativeText, $readableCharacters);

        // Reproduce el archivo real: supera el umbral antiguo de 80 caracteres,
        // pero la capa nativa solo describe el sello/QR y no el cuerpo escaneado.
        $this->assertGreaterThanOrEqual(80, count($readableCharacters[0]));
        $this->assertLessThan(200, count($readableCharacters[0]));

        $document = $this->document([
            'file_name' => 'acuerdo-con-qr.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('extractDocumentText')
            ->once()
            ->with('application/pdf', $pdf)
            ->andReturn(
                "[[PAGINA:1]]\nACUERDO DEL CONCEJO N. 074-2025-MDE\n"
                .'ARTICULO PRIMERO: APROBAR que el PROVALE alcance el padron de beneficiarios.'
            );
        $ai->shouldReceive('embedText')
            ->once()
            ->with(Mockery::on(fn (string $text) => str_contains($text, 'padron de beneficiarios')), 'RETRIEVAL_DOCUMENT')
            ->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('google');

        (new KnowledgeBaseRagService($ai))->indexDocument($document, $pdf);

        $chunk = $document->fresh()->chunks()->sole();
        $this->assertSame('INDEXADO', $document->fresh()->index_status);
        $this->assertStringContainsString('ACUERDO DEL CONCEJO N. 074-2025-MDE', $chunk->content);
        $this->assertStringContainsString('padron de beneficiarios', $chunk->content);
    }

    public function test_it_uses_visual_ocr_for_an_uploaded_image(): void
    {
        $document = $this->document([
            'file_name' => 'resolucion.png',
            'mime_type' => 'image/png',
        ]);
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('extractDocumentText')
            ->once()
            ->with('image/png', 'imagen-binaria')
            ->andReturn('Resolución de alcaldía 042-2026');
        $ai->shouldReceive('embedText')->once()->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('google');

        (new KnowledgeBaseRagService($ai))->indexDocument($document, 'imagen-binaria');

        $chunk = $document->fresh()->chunks()->sole();
        $this->assertSame(1, $chunk->page_number);
        $this->assertStringContainsString('Resolución de alcaldía 042-2026', $chunk->content);
    }

    public function test_indexing_failure_marks_document_as_error_without_losing_existing_chunks(): void
    {
        $document = $this->document([
            'file_name' => 'roto.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        KnowledgeBaseDocumentChunk::create([
            'kb_document_id' => $document->id,
            'chunk_index' => 1,
            'content' => 'fragmento recuperable',
        ]);
        $ai = Mockery::mock(AssistantAiService::class);

        try {
            (new KnowledgeBaseRagService($ai))->indexDocument($document, 'no-es-un-zip');
            $this->fail('La indexación de un DOCX inválido debía fallar.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('DOCX', $exception->getMessage());
        }

        $document->refresh();
        $this->assertSame('ERROR', $document->index_status);
        $this->assertStringContainsString('DOCX', $document->index_error);
        $this->assertSame('fragmento recuperable', $document->chunks()->sole()->content);
    }

    public function test_vector_search_ranks_semantic_match_and_excludes_unindexed_documents(): void
    {
        config()->set('rag.embedding_dim', 2);
        config()->set('rag.semantic_weight', 1.0);
        $best = $this->document(['title' => 'Leche', 'index_status' => 'INDEXADO']);
        $other = $this->document(['title' => 'Arroz', 'index_status' => 'INDEXADO']);
        $pending = $this->document(['title' => 'Pendiente', 'index_status' => 'PENDIENTE']);
        $this->chunk($best, 'Entrega mensual de leche.', [1, 0]);
        $this->chunk($other, 'Compra de arroz.', [0, 1]);
        $this->chunk($pending, 'Documento no disponible.', [1, 0]);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->with('entrega láctea', 'RETRIEVAL_QUERY')->andReturn([10, 0]);

        $matches = (new KnowledgeBaseRagService($ai))->search('entrega láctea', 10);

        $this->assertCount(2, $matches);
        $this->assertSame($best->id, $matches[0]['metadata']['document_id']);
        $this->assertSame(1.0, $matches[0]['score']);
        $this->assertTrue($matches[0]['metadata']['has_embedding']);
        $this->assertNotContains($pending->id, array_column(array_column($matches, 'metadata'), 'document_id'));
    }

    public function test_answer_does_not_invent_when_no_relevant_chunk_exists(): void
    {
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('groq');
        $ai->shouldNotReceive('generate');

        $result = (new KnowledgeBaseRagService($ai))->answer('¿Cuál es el presupuesto anual?');

        $this->assertSame([], $result['sources']);
        $this->assertStringContainsString('No encontré información', $result['answer']);
    }

    public function test_answer_sends_only_retrieved_context_and_returns_traceable_sources(): void
    {
        config()->set('rag.embedding_dim', 2);
        config()->set('knowledge_base.min_score', 0.01);
        $document = $this->document([
            'title' => 'Directiva de entregas',
            'file_name' => 'directiva.pdf',
            'index_status' => 'INDEXADO',
        ]);
        $this->chunk($document, 'El plazo máximo de entrega es de diez días hábiles.', [1, 0], 4);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->andReturn([1, 0]);
        $ai->shouldReceive('generate')
            ->once()
            ->with(
                [['role' => 'user', 'content' => '¿Cuál es el plazo máximo?']],
                Mockery::on(fn (string $prompt) => str_contains($prompt, '[Fuente 1] (directiva.pdf)')
                    && str_contains($prompt, 'diez días hábiles')
                    && str_contains($prompt, 'Usa Markdown claro')
                    && str_contains($prompt, 'nunca dejes una frase')),
            )
            ->andReturn('El plazo es de diez días hábiles [Fuente 1].');

        $result = (new KnowledgeBaseRagService($ai))->answer('¿Cuál es el plazo máximo?');

        $this->assertSame('El plazo es de diez días hábiles [Fuente 1].', $result['answer']);
        $this->assertSame($document->id, $result['sources'][0]['document_id']);
        $this->assertSame('directiva.pdf', $result['sources'][0]['archivo']);
        $this->assertSame(4, $result['sources'][0]['pagina']);
        $this->assertStringContainsString('diez días', $result['sources'][0]['extracto']);
    }

    public function test_answer_can_use_an_indexed_municipal_norm_as_a_traceable_source(): void
    {
        config()->set('knowledge_base.min_score', 0.01);

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('embedText')->once()->andReturn(null);
        $ai->shouldReceive('provider')->once()->andReturn('groq');
        $ai->shouldReceive('generate')
            ->once()
            ->withArgs(fn (array $messages, string $prompt) => $messages[0]['content'] === '¿Quién integra el comité?'
                && str_contains($prompt, 'RESOLUCION DE ALCALDIA N° 0750-2026-MDE')
                && str_contains($prompt, 'Comité de Administración del Programa de Vaso de Leche'))
            ->andReturn('El comité incluye representantes municipales y del programa [Fuente 1].');

        $normativa = Mockery::mock(NormativaRagService::class);
        $normativa->shouldReceive('search')->once()->andReturn([[
            'content' => 'Comité de Administración del Programa de Vaso de Leche.',
            'score' => 0.91,
            'metadata' => [
                'document_id' => 75,
                'nombre_archivo' => 'RESOLUCION DE ALCALDIA N° 0750-2026-MDE',
                'pagina' => 1,
                'chunk' => 1,
                'origen' => 'normativa_municipal',
                'download_url' => 'https://www.muniesperanza.gob.pe/norma.pdf',
            ],
        ]]);

        $result = (new KnowledgeBaseRagService($ai, $normativa))->answer('¿Quién integra el comité?');

        $this->assertSame('normativa_municipal', $result['sources'][0]['origen']);
        $this->assertSame('https://www.muniesperanza.gob.pe/norma.pdf', $result['sources'][0]['download_url']);
        $this->assertSame(75, $result['sources'][0]['document_id']);
    }

    private function document(array $overrides = []): KnowledgeBaseDocument
    {
        return KnowledgeBaseDocument::create(array_merge([
            'title' => 'Documento',
            'file_name' => 'documento.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'file_hash' => hash('sha256', uniqid('kb-rag', true)),
            'file_data' => base64_encode('legacy'),
            'index_status' => 'PENDIENTE',
        ], $overrides));
    }

    private function chunk(
        KnowledgeBaseDocument $document,
        string $content,
        ?array $embedding,
        int $page = 1,
    ): KnowledgeBaseDocumentChunk {
        return KnowledgeBaseDocumentChunk::create([
            'kb_document_id' => $document->id,
            'page_number' => $page,
            'chunk_index' => 1,
            'content' => $content,
            'embedding' => $embedding,
            'metadata' => [],
        ]);
    }

    private function docx(string $text): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kb_docx_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body><w:p><w:r><w:t>'.htmlspecialchars($text, ENT_XML1, 'UTF-8').'</w:t></w:r></w:p></w:body>'
            .'</w:document>'
        );
        $zip->close();
        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    private function xlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Beneficiarios');
        $sheet->fromArray([
            ['DNI', 'Nombre'],
            ['12345678', 'María Quispe'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'kb_xlsx_');
        (new Xlsx($spreadsheet))->save($path);
        $binary = (string) file_get_contents($path);
        @unlink($path);
        $spreadsheet->disconnectWorksheets();

        return $binary;
    }

    private function pdf(string $text): string
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<pre>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</pre>');
        $dompdf->render();

        return $dompdf->output();
    }
}
