<?php

namespace App\Services\Normativa;

use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use App\Services\Rag\BaseRagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;

class NormativaRagService extends BaseRagService
{
    public function indexDocument(NormativaDocument $document, string $pdfBinary): void
    {
        $document->forceFill(['index_status' => 'INDEXANDO', 'index_error' => null])->save();

        try {
            $extraction = $this->extractText($pdfBinary, $document);
            $text = $extraction['text'];
            if (trim($text) === '') {
                throw new RuntimeException('No se encontró texto legible en el PDF.');
            }

            $size = (int) config('normativa.chunk_size', 1200);
            $overlap = (int) config('normativa.chunk_overlap', 200);
            $maxChunks = (int) config('rag.max_chunks_normativa', 60);
            $chunks = $this->chunkPages($text, $size, $overlap, $maxChunks);
            if ($chunks === []) {
                throw new RuntimeException('No fue posible fragmentar el documento.');
            }

            $embeddingModel = (string) config('rag.embedding_model', '');

            DB::transaction(function () use ($document, $chunks, $embeddingModel, $extraction) {
                $document->chunks()->delete();

                foreach ($chunks as $index => $chunk) {
                    NormativaDocumentChunk::create([
                        'normativa_document_id' => $document->id,
                        'page_number' => $chunk['page'],
                        'chunk_index' => $index + 1,
                        'content' => $chunk['content'],
                        'embedding' => $this->validatedEmbedding(
                            $this->ai->embedText($chunk['content'], 'RETRIEVAL_DOCUMENT'),
                            'normativa:index:'.$document->id
                        ),
                        'metadata' => [
                            'document_id' => $document->id,
                            'tipo_documento' => $document->tipo_documento,
                            'numero' => $document->numero,
                            'periodo' => $document->periodo,
                            'pagina' => $chunk['page'],
                            'chunk' => $index + 1,
                            'embedding_model' => $embeddingModel ?: null,
                            'text_source' => $extraction['source'],
                        ],
                    ]);
                }

                $document->forceFill(['index_status' => 'INDEXADO', 'index_error' => null])->save();
            });
        } catch (\Throwable $exception) {
            $document->forceFill([
                'index_status' => 'ERROR',
                'index_error' => mb_substr($exception->getMessage(), 0, 1000),
            ])->save();

            throw $exception;
        }
    }

    /**
     * Búsqueda híbrida (antes inexistente: normativa solo indexaba).
     *
     * @return array<int, array{content:string,score:float,metadata:array}>
     */
    public function search(string $query, int $limit = 8): array
    {
        $queryEmbedding = $this->validatedEmbedding(
            $this->ai->embedText($query, 'RETRIEVAL_QUERY'),
            'normativa:search'
        );
        $this->warnIfNoEmbedding($queryEmbedding, 'normativa:search');

        $candidateLimit = $queryEmbedding !== null
            ? (int) config('rag.candidate_limit', 500)
            : (int) config('rag.lexical_candidate_limit', 200);

        $eloquent = NormativaDocumentChunk::query()
            ->select(['id', 'normativa_document_id', 'content', 'embedding', 'page_number', 'chunk_index'])
            ->with('document:id,tipo_documento,numero,periodo,titulo,pdf_url,file_path,index_status')
            ->whereHas('document', fn ($documents) => $documents->where('index_status', 'INDEXADO'))
            ->orderBy('id');

        if ($queryEmbedding === null) {
            $eloquent->where(function ($where) use ($query) {
                foreach ($this->keywords($query) as $keyword) {
                    $where->orWhere('content', 'like', '%'.$keyword.'%');
                }
            });
        }

        $chunks = $eloquent->limit($candidateLimit)->get();

        return $chunks
            ->map(function (NormativaDocumentChunk $chunk) use ($queryEmbedding, $query) {
                $chunkEmbedding = $this->validatedEmbedding($chunk->embedding, 'normativa:score');
                $score = $this->hybridScore($queryEmbedding, $chunkEmbedding, $query, $chunk->content);

                return [
                    'content' => $chunk->content,
                    'score' => round($score, 6),
                    'metadata' => [
                        'document_id' => $chunk->document->id,
                        'tipo_documento' => $chunk->document->tipo_documento,
                        'numero' => $chunk->document->numero,
                        'periodo' => $chunk->document->periodo,
                        'titulo' => $chunk->document->titulo,
                        'nombre_archivo' => trim($chunk->document->tipo_documento.' '.($chunk->document->numero ? 'N° '.$chunk->document->numero : '')),
                        'download_url' => $chunk->document->file_path
                            ? route('api.base-conocimiento.normativa.preview', $chunk->document)
                            : $chunk->document->pdf_url,
                        'origen' => 'normativa_municipal',
                        'pagina' => $chunk->page_number,
                        'chunk' => $chunk->chunk_index,
                        'has_embedding' => $chunkEmbedding !== null && $queryEmbedding !== null,
                    ],
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array{text:string,source:string} */
    private function extractText(string $binary, NormativaDocument $document): array
    {
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
                return ['text' => implode(PHP_EOL.PHP_EOL, $pages), 'source' => 'pdf_text'];
            }
        } catch (\Throwable) {
            // Se intenta extracción vía IA a continuación.
        }

        try {
            $text = $this->ai->extractDocumentText('application/pdf', $binary);
            if ($text !== null && trim($text) !== '') {
                return ['text' => $text, 'source' => 'ocr_ai'];
            }
        } catch (\Throwable $exception) {
            Log::warning('Falló el OCR de una norma; se indexarán sus metadatos oficiales.', [
                'document_id' => $document->id,
                'provider' => $this->ai->provider(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $metadataText = $this->officialMetadataText($document);
        if ($metadataText !== '') {
            return ['text' => $metadataText, 'source' => 'portal_metadata'];
        }

        return ['text' => '', 'source' => 'none'];
    }

    private function officialMetadataText(NormativaDocument $document): string
    {
        $fields = array_filter([
            'Documento: '.$document->titulo,
            $document->tipo_documento ? 'Tipo: '.$document->tipo_documento : null,
            $document->numero ? 'Número: '.$document->numero : null,
            $document->fecha_documento ? 'Fecha: '.$document->fecha_documento->format('d/m/Y') : null,
            $document->asunto ? 'Asunto: '.$document->asunto : null,
            $document->concepto ? 'Concepto: '.$document->concepto : null,
        ]);

        return $fields === [] ? '' : '[[PAGINA:1]]'.PHP_EOL.implode(PHP_EOL, $fields);
    }
}
