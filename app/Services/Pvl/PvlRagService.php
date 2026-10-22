<?php

namespace App\Services\Pvl;

use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Repositories\Contracts\PvlDocumentRepositoryInterface;
use App\Services\AssistantAiService;
use App\Services\Rag\BaseRagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;

class PvlRagService extends BaseRagService
{
    public function __construct(AssistantAiService $ai, private PvlDocumentRepositoryInterface $documents)
    {
        parent::__construct($ai);
    }

    public function indexDocument(PvlDocument $document, string $binary): void
    {
        $this->documents->updateDocument($document, ['index_status' => 'INDEXANDO', 'index_error' => null]);

        try {
            $text = $this->extractText($document->mime_type, $binary);
            if (trim($text) === '') {
                throw new RuntimeException('No se encontró texto legible en el documento.');
            }

            $size = (int) config('pvl_reports.chunk_size', 1200);
            $overlap = (int) config('pvl_reports.chunk_overlap', 200);
            $maxChunks = (int) config('rag.max_chunks_pvl', 120);
            $chunks = $this->chunkPages($text, $size, $overlap, $maxChunks);
            if ($chunks === []) {
                throw new RuntimeException('No fue posible fragmentar el documento.');
            }

            $embeddingModel = (string) config('rag.embedding_model', '');

            DB::transaction(function () use ($document, $chunks, $embeddingModel) {
                $this->documents->deleteChunks($document);

                foreach ($chunks as $index => $chunk) {
                    $metadata = [
                        'document_id' => $document->id,
                        'tipo_documento' => $document->document_type,
                        'periodo' => $document->period,
                        'producto_id' => $document->product_id,
                        'proveedor_referencia' => $document->provider_reference,
                        'nombre_archivo' => $document->file_name,
                        'pagina' => $chunk['page'],
                        'chunk' => $index + 1,
                        'embedding_model' => $embeddingModel ?: null,
                    ];

                    $this->documents->createChunk([
                        'pvl_document_id' => $document->id,
                        'page_number' => $chunk['page'],
                        'chunk_index' => $index + 1,
                        'content' => $chunk['content'],
                        'embedding' => $this->validatedEmbedding(
                            $this->ai->embedText($chunk['content'], 'RETRIEVAL_DOCUMENT'),
                            'pvl:index:'.$document->id
                        ),
                        'metadata' => $metadata,
                    ]);
                }

                $this->documents->updateDocument($document, ['index_status' => 'INDEXADO', 'index_error' => null]);
            });
        } catch (\Throwable $exception) {
            $this->documents->updateDocument($document, [
                'index_status' => 'ERROR',
                'index_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<int, array{content:string,score:float,metadata:array}>
     */
    public function search(string $reportType, int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $types = $this->typesFor($reportType);
        $query = $this->queryFor($reportType, $period);
        $queryEmbedding = $this->validatedEmbedding(
            $this->ai->embedText($query, 'RETRIEVAL_QUERY'),
            'pvl:search:'.$period
        );
        $this->warnIfNoEmbedding($queryEmbedding, 'pvl:search:'.$period);

        $candidateLimit = (int) config('rag.candidate_limit', 500);

        $keywords = $queryEmbedding === null ? $this->keywords($query) : null;
        if ($queryEmbedding === null) {
            $candidateLimit = min($candidateLimit, (int) config('rag.lexical_candidate_limit', 200));
        }

        $chunks = $this->documents->searchChunks($period, $types, $keywords, $candidateLimit);

        if ($chunks->isEmpty() && $queryEmbedding === null) {
            // Prefiltro demasiado estricto (ej. singular/plural): fallback
            // a los candidatos del periodo sin filtro léxico.
            $chunks = $this->documents->searchChunks($period, $types, null, $candidateLimit);
        }

        return $chunks
            ->map(function (PvlDocumentChunk $chunk) use ($queryEmbedding, $query) {
                $chunkEmbedding = $this->validatedEmbedding($chunk->embedding, 'pvl:score');
                $score = $this->hybridScore($queryEmbedding, $chunkEmbedding, $query, $chunk->content);

                return [
                    'content' => $chunk->content,
                    'score' => round($score, 6),
                    'metadata' => array_merge($chunk->metadata ?? [], [
                        'document_id' => $chunk->document->id,
                        'tipo_documento' => $chunk->document->document_type,
                        'periodo' => $chunk->document->period,
                        'producto_id' => $chunk->document->product_id,
                        'proveedor_referencia' => $chunk->document->provider_reference,
                        'nombre_archivo' => $chunk->document->file_name,
                        'pagina' => $chunk->page_number,
                        'chunk' => $chunk->chunk_index,
                        'has_embedding' => $chunkEmbedding !== null && $queryEmbedding !== null,
                    ]),
                ];
            })
            ->sortByDesc('score')
            ->take((int) config('pvl_reports.rag_limit', 12))
            ->values()
            ->all();
    }

    public function sourceFingerprint(string $reportType, int $year, int $month, array $context): string
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $stableContext = $context;
        data_forget($stableContext, 'pvl.fecha_hora_impresion');
        data_forget($stableContext, 'pvl.fecha_reporte');
        data_forget($stableContext, 'racion_a.fecha_reporte');
        $documents = $this->documents->indexedDocuments($period, $this->typesFor($reportType))
            ->map(fn (PvlDocument $document) => [
                $document->id,
                $document->file_hash,
                optional($document->updated_at)->format('Y-m-d H:i:s.u'),
            ])
            ->all();

        return hash('sha256', json_encode([
            'context' => $stableContext,
            'documents' => $documents,
            'prompt' => config('pvl_reports.prompt_version'),
            'ai_model' => $this->ai->modelIdentifier(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function extractText(string $mimeType, string $binary): string
    {
        $plainTypes = [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/json',
            'application/xml',
            'text/xml',
        ];

        if (in_array($mimeType, $plainTypes, true) || str_starts_with($mimeType, 'text/')) {
            return mb_convert_encoding($binary, 'UTF-8', ['UTF-8', 'ISO-8859-1', 'Windows-1252']);
        }

        if ($mimeType === 'application/pdf') {
            try {
                $pdf = (new Parser())->parseContent($binary);
                $pages = [];

                foreach ($pdf->getPages() as $index => $page) {
                    $pageText = trim($page->getText());
                    if ($pageText !== '') {
                        $pages[] = '[[PAGINA:'.($index + 1).']]'.PHP_EOL.$pageText;
                    }
                }

                if ($pages !== []) {
                    return implode(PHP_EOL.PHP_EOL, $pages);
                }
            } catch (\Throwable) {
                // Google todavía puede leer el PDF directamente; con Groq se
                // informará que el archivo requiere texto seleccionable.
            }
        }

        $text = $this->ai->extractDocumentText($mimeType, $binary);

        if ($text === null) {
            throw new RuntimeException(
                $mimeType === 'application/pdf' && $this->ai->provider() === 'groq'
                    ? 'El PDF no contiene texto extraíble. Con Groq, cargue un PDF con texto seleccionable o las páginas como PNG/JPG.'
                    : $this->ai->providerLabel().' no pudo extraer texto del documento.'
            );
        }

        return $text;
    }

    /** @return array<int, string> */
    private function typesFor(string $reportType): array
    {
        $pvl = ['factura', 'comprobante', 'orden_compra', 'financiamiento', 'donacion', 'proveedor', 'constancia_envio', 'otro'];
        $ration = ['distribucion', 'certificado_calidad', 'certificado_microbiologico', 'ficha_tecnica', 'lote', 'beneficiarios', 'constancia_envio', 'otro'];

        return match ($reportType) {
            'PVL' => $pvl,
            'RACION_A' => $ration,
            default => array_values(array_unique(array_merge($pvl, $ration))),
        };
    }

    private function queryFor(string $reportType, string $period): string
    {
        $pvl = 'facturas comprobantes proveedores órdenes de compra alimentos insumos donaciones financiamiento transferencias responsables constancia de envío código de envío';
        $ration = 'distribución entrega certificados bromatológicos microbiológicos fichas técnicas lotes vencimientos composición beneficiarios comités responsables constancia de envío código de envío';

        return "Reporte {$reportType} del periodo {$period}. ".($reportType === 'PVL' ? $pvl : ($reportType === 'RACION_A' ? $ration : $pvl.' '.$ration));
    }
}
