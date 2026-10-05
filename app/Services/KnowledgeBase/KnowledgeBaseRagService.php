<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Services\Rag\BaseRagService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class KnowledgeBaseRagService extends BaseRagService
{
    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private const XLS_MIME = 'application/vnd.ms-excel';

    public function indexDocument(KnowledgeBaseDocument $document, string $binary): void
    {
        $document->forceFill(['index_status' => 'INDEXANDO', 'index_error' => null])->save();

        try {
            $text = $this->extractText($document->mime_type, $document->file_name, $binary);
            if (trim($text) === '') {
                throw new RuntimeException('No se encontró texto legible en el documento.');
            }

            $size = (int) config('knowledge_base.chunk_size', 1200);
            $overlap = (int) config('knowledge_base.chunk_overlap', 200);
            $maxChunks = (int) config('rag.max_chunks_kb', 200);
            $chunks = $this->chunkPages($text, $size, $overlap, $maxChunks);
            if ($chunks === []) {
                throw new RuntimeException('No fue posible fragmentar el documento.');
            }

            $embeddingModel = (string) config('rag.embedding_model', '');

            DB::transaction(function () use ($document, $chunks, $embeddingModel) {
                $document->chunks()->delete();

                foreach ($chunks as $index => $chunk) {
                    $embedding = $this->validatedEmbedding(
                        $this->ai->embedText($chunk['content'], 'RETRIEVAL_DOCUMENT'),
                        'kb:index:'.$document->id
                    );

                    KnowledgeBaseDocumentChunk::create([
                        'kb_document_id' => $document->id,
                        'page_number' => $chunk['page'],
                        'chunk_index' => $index + 1,
                        'content' => $chunk['content'],
                        'embedding' => $embedding,
                        'metadata' => [
                            'document_id' => $document->id,
                            'titulo' => $document->title,
                            'nombre_archivo' => $document->file_name,
                            'pagina' => $chunk['page'],
                            'chunk' => $index + 1,
                            'embedding_model' => $embeddingModel ?: null,
                            'has_embedding' => $embedding !== null,
                        ],
                    ]);
                }

                $document->forceFill(['index_status' => 'INDEXADO', 'index_error' => null])->save();
            });

            if ($this->chunksWithoutEmbedding($document->id) > 0) {
                Log::warning('Documento KB indexado sin embeddings; responderá solo con búsqueda léxica.', [
                    'document_id' => $document->id,
                    'provider' => $this->ai->provider(),
                ]);
            }
        } catch (\Throwable $exception) {
            $document->forceFill([
                'index_status' => 'ERROR',
                'index_error' => mb_substr($exception->getMessage(), 0, 1000),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  array<int, int>  $documentIds  Prefiltro opcional por documentos.
     * @return array<int, array{content:string,score:float,metadata:array}>
     */
    public function search(string $query, int $limit, array $documentIds = []): array
    {
        $queryEmbedding = $this->validatedEmbedding(
            $this->ai->embedText($query, 'RETRIEVAL_QUERY'),
            'kb:search'
        );
        $this->warnIfNoEmbedding($queryEmbedding, 'kb:search');

        $candidateLimit = $queryEmbedding !== null
            ? (int) config('rag.candidate_limit', 500)
            : (int) config('rag.lexical_candidate_limit', 200);

        $eloquent = KnowledgeBaseDocumentChunk::query()
            ->select(['id', 'kb_document_id', 'content', 'embedding', 'page_number', 'chunk_index'])
            ->with('document:id,title,file_name,index_status')
            ->whereHas('document', function ($documents) use ($documentIds) {
                $documents->where('index_status', 'INDEXADO');
                if ($documentIds !== []) {
                    $documents->whereIn('id', $documentIds);
                }
            })
            ->orderBy('id');

        if ($queryEmbedding === null) {
            // Sin vectores: prefiltrado SQL por keywords para no traer 1000 filas.
            $eloquent->where(function ($where) use ($query) {
                foreach ($this->keywords($query) as $keyword) {
                    $where->orWhere('content', 'like', '%'.$keyword.'%');
                }
            });
        }

        $chunks = $eloquent->limit($candidateLimit)->get();

        return $chunks
            ->map(function (KnowledgeBaseDocumentChunk $chunk) use ($queryEmbedding, $query) {
                $chunkEmbedding = $this->validatedEmbedding($chunk->embedding, 'kb:score');
                $score = $this->hybridScore($queryEmbedding, $chunkEmbedding, $query, $chunk->content);

                return [
                    'content' => $chunk->content,
                    'score' => round($score, 6),
                    'metadata' => [
                        'document_id' => $chunk->document->id,
                        'titulo' => $chunk->document->title,
                        'nombre_archivo' => $chunk->document->file_name,
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

    /**
     * @return array{answer:string, sources:array}
     */
    public function answer(string $question): array
    {
        $limit = (int) config('knowledge_base.rag_limit', 8);
        $minScore = (float) config('knowledge_base.min_score', 0.05);
        $matches = $this->search($question, $limit);
        $relevant = array_values(array_filter($matches, fn (array $match) => $match['score'] > $minScore));

        if ($relevant === []) {
            return [
                'answer' => 'No encontré información relacionada con esa pregunta en los documentos indexados.',
                'sources' => [],
            ];
        }

        $context = collect($relevant)
            ->map(fn (array $match, int $index) => sprintf(
                "[Fuente %d] (%s)\n%s",
                $index + 1,
                $match['metadata']['nombre_archivo'],
                $match['content'],
            ))
            ->implode("\n\n---\n\n");

        $systemPrompt = 'Eres un asistente que responde preguntas usando EXCLUSIVAMENTE la información del '
            .'CONTEXTO proporcionado a continuación, extraído de documentos indexados. Si la respuesta no está '
            .'en el contexto, indica claramente que no encontraste esa información en los documentos. No '
            .'inventes datos. Cita la fuente entre corchetes (ej. [Fuente 1]) al final de cada afirmación que '
            .'provenga del contexto. Responde de forma completa y termina todas las ideas; nunca dejes una frase '
            .'o enumeración inconclusa. Usa Markdown claro para facilitar la lectura: títulos breves cuando sean '
            .'necesarios, negritas para conceptos importantes y listas numeradas o con viñetas para varios puntos. '
            .'No incluyas una sección de fuentes separada porque la interfaz ya muestra las fuentes utilizadas.'
            ."\n\nCONTEXTO:\n".$context;

        $answer = $this->ai->generate([['role' => 'user', 'content' => $question]], $systemPrompt);

        if ($answer === null) {
            throw new RuntimeException(
                $this->ai->providerLabel().' no pudo generar una respuesta. Verifique la configuración de IA.'
            );
        }

        return [
            'answer' => $answer,
            'sources' => collect($relevant)->map(fn (array $match) => [
                'document_id' => $match['metadata']['document_id'],
                'archivo' => $match['metadata']['nombre_archivo'],
                'pagina' => $match['metadata']['pagina'],
                'chunk' => $match['metadata']['chunk'],
                'score' => $match['score'],
                'extracto' => mb_substr($match['content'], 0, 240),
            ])->values()->all(),
        ];
    }

    private function chunksWithoutEmbedding(int $documentId): int
    {
        // embedding JSON null o 'null' serializado.
        return KnowledgeBaseDocumentChunk::query()
            ->where('kb_document_id', $documentId)
            ->where(function ($where) {
                $where->whereNull('embedding')->orWhere('embedding', 'like', '%null%');
            })
            ->count();
    }

    private function extractText(string $mimeType, string $fileName, string $binary): string
    {
        if ($mimeType === 'application/pdf') {
            $nativePages = [];

            try {
                $pdf = (new Parser())->parseContent($binary);

                foreach ($pdf->getPages() as $index => $page) {
                    $nativePages[$index + 1] = trim($page->getText());
                }

                $minimumCharacters = max(0, (int) config('knowledge_base.ocr_min_page_characters', 200));
                $hasScannedPage = $nativePages === [] || collect($nativePages)->contains(
                    fn (string $text) => $this->readableCharacterCount($text) < $minimumCharacters
                );

                if (! $hasScannedPage) {
                    return $this->joinPdfPages($nativePages);
                }
            } catch (\Throwable) {
                // Se intenta extracción vía IA a continuación.
            }

            // Un PDF mixto debe enviarse completo: Gemini combina la capa de
            // texto con la información visual de las páginas escaneadas.
            $text = $this->ai->extractDocumentText($mimeType, $binary);
            if ($text !== null && trim($text) !== '') {
                return $text;
            }

            throw new RuntimeException(
                $this->ai->providerLabel().' no pudo reconocer texto en las páginas escaneadas de este PDF.'
            );
        }

        if (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            $text = $this->ai->extractDocumentText($mimeType, $binary);
            if ($text !== null && trim($text) !== '') {
                return '[[PAGINA:1]]'.PHP_EOL.trim($text);
            }

            throw new RuntimeException($this->ai->providerLabel().' no pudo reconocer texto en la imagen.');
        }

        if ($mimeType === self::DOCX_MIME || str_ends_with(strtolower($fileName), '.docx')) {
            return $this->extractDocx($binary);
        }

        if ($mimeType === self::XLSX_MIME || $mimeType === self::XLS_MIME
            || str_ends_with(strtolower($fileName), '.xlsx') || str_ends_with(strtolower($fileName), '.xls')) {
            $extension = str_ends_with(strtolower($fileName), '.xls') ? 'xls' : 'xlsx';

            return $this->extractSpreadsheet($binary, $extension);
        }

        throw new RuntimeException('Tipo de documento no compatible. Cargue un PDF, JPG, PNG, DOCX, XLS o XLSX.');
    }

    /** @param array<int, string> $pages */
    private function joinPdfPages(array $pages): string
    {
        return collect($pages)
            ->filter(fn (string $text) => trim($text) !== '')
            ->map(fn (string $text, int $page) => '[[PAGINA:'.$page.']]'.PHP_EOL.trim($text))
            ->implode(PHP_EOL.PHP_EOL);
    }

    private function readableCharacterCount(string $text): int
    {
        return preg_match_all('/[\pL\pN]/u', $text) ?: 0;
    }

    private function extractDocx(string $binary): string
    {
        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kb_'.bin2hex(random_bytes(8)).'.docx';
        file_put_contents($tmpPath, $binary);

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                throw new RuntimeException('No se pudo leer el archivo DOCX.');
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                throw new RuntimeException('El archivo DOCX no contiene un documento válido.');
            }

            $xml = str_replace(['</w:p>', '<w:tab/>', '<w:br/>'], ["\n", "\t", "\n"], $xml);
            $text = strip_tags($xml);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

            return trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
        } finally {
            @unlink($tmpPath);
        }
    }

    private function extractSpreadsheet(string $binary, string $extension): string
    {
        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kb_'.bin2hex(random_bytes(8)).'.'.$extension;
        file_put_contents($tmpPath, $binary);

        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $lines = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $lines[] = '[[HOJA:'.$sheet->getTitle().']]';

                foreach ($sheet->toArray(null, true, true, false) as $row) {
                    $cells = array_values(array_filter(
                        array_map(static fn ($value) => trim((string) $value), $row),
                        static fn ($value) => $value !== ''
                    ));

                    if ($cells !== []) {
                        $lines[] = implode(' | ', $cells);
                    }
                }
            }

            return trim(implode("\n", $lines));
        } finally {
            @unlink($tmpPath);
        }
    }
}
