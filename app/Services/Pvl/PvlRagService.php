<?php

namespace App\Services\Pvl;

use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Services\AssistantAiService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Smalot\PdfParser\Parser;

class PvlRagService
{
    public function __construct(private AssistantAiService $ai)
    {
    }

    public function indexDocument(PvlDocument $document, string $binary): void
    {
        $document->forceFill(['index_status' => 'INDEXANDO', 'index_error' => null])->save();

        try {
            $text = $this->extractText($document->mime_type, $binary);
            if (trim($text) === '') {
                throw new RuntimeException('No se encontró texto legible en el documento.');
            }

            $chunks = $this->chunkPages($text);
            if ($chunks === []) {
                throw new RuntimeException('No fue posible fragmentar el documento.');
            }

            DB::transaction(function () use ($document, $chunks) {
                $document->chunks()->delete();

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
                    ];

                    PvlDocumentChunk::create([
                        'pvl_document_id' => $document->id,
                        'page_number' => $chunk['page'],
                        'chunk_index' => $index + 1,
                        'content' => $chunk['content'],
                        'embedding' => $this->ai->embedText($chunk['content'], 'RETRIEVAL_DOCUMENT'),
                        'metadata' => $metadata,
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
     * @return array<int, array{content:string,score:float,metadata:array}>
     */
    public function search(string $reportType, int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $types = $this->typesFor($reportType);
        $query = $this->queryFor($reportType, $period);
        $queryEmbedding = $this->ai->embedText($query, 'RETRIEVAL_QUERY');

        $chunks = PvlDocumentChunk::query()
            ->with('document:id,document_type,period,product_id,provider_reference,file_name,file_hash,index_status')
            ->whereHas('document', fn ($documents) => $documents
                ->where('period', $period)
                ->where('index_status', 'INDEXADO')
                ->whereIn('document_type', $types))
            ->limit(500)
            ->get();

        return $chunks
            ->map(function (PvlDocumentChunk $chunk) use ($queryEmbedding, $query) {
                $semantic = $queryEmbedding && $chunk->embedding
                    ? $this->cosineSimilarity($queryEmbedding, $chunk->embedding)
                    : 0.0;
                $lexical = $this->lexicalScore($query, $chunk->content);
                $score = $queryEmbedding && $chunk->embedding
                    ? ($semantic * 0.85) + ($lexical * 0.15)
                    : $lexical;

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
        $documents = PvlDocument::query()
            ->where('period', $period)
            ->where('index_status', 'INDEXADO')
            ->whereIn('document_type', $this->typesFor($reportType))
            ->orderBy('id')
            ->get(['id', 'file_hash', 'updated_at'])
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

    /** @return array<int, array{page:int|null,content:string}> */
    private function chunkPages(string $text): array
    {
        $pages = $this->pages($text);
        $size = max(400, (int) config('pvl_reports.chunk_size', 1200));
        $overlap = min($size - 100, max(0, (int) config('pvl_reports.chunk_overlap', 200)));
        $chunks = [];

        foreach ($pages as $page => $content) {
            $content = trim(preg_replace('/[\t ]+/u', ' ', preg_replace('/\R{3,}/u', "\n\n", $content)) ?? $content);
            $length = mb_strlen($content);

            for ($offset = 0; $offset < $length; $offset += ($size - $overlap)) {
                $chunk = trim(mb_substr($content, $offset, $size));
                if ($chunk !== '') {
                    $chunks[] = ['page' => $page, 'content' => $chunk];
                }
            }
        }

        return array_slice($chunks, 0, 120);
    }

    /** @return array<int, string> */
    private function pages(string $text): array
    {
        if (! preg_match('/\[\[PAGINA:(\d+)\]\]/u', $text)) {
            return [1 => $text];
        }

        $parts = preg_split('/\[\[PAGINA:(\d+)\]\]/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pages = [];

        for ($index = 1; $index < count($parts); $index += 2) {
            $page = (int) $parts[$index];
            $pages[$page] = ($pages[$page] ?? '').($parts[$index + 1] ?? '');
        }

        return $pages ?: [1 => $text];
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

    /** @param array<int, float|int> $left @param array<int, float|int> $right */
    private function cosineSimilarity(array $left, array $right): float
    {
        $length = min(count($left), count($right));
        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        for ($index = 0; $index < $length; $index++) {
            $a = (float) $left[$index];
            $b = (float) $right[$index];
            $dot += $a * $b;
            $leftNorm += $a * $a;
            $rightNorm += $b * $b;
        }

        return ($leftNorm > 0 && $rightNorm > 0)
            ? $dot / (sqrt($leftNorm) * sqrt($rightNorm))
            : 0.0;
    }

    private function lexicalScore(string $query, string $content): float
    {
        preg_match_all('/[\pL\pN]{4,}/u', mb_strtolower($query), $queryWords);
        preg_match_all('/[\pL\pN]{4,}/u', mb_strtolower($content), $contentWords);
        $needles = array_unique($queryWords[0] ?? []);
        if ($needles === []) {
            return 0.0;
        }

        $haystack = array_flip(array_unique($contentWords[0] ?? []));
        $matches = count(array_filter($needles, static fn ($word) => isset($haystack[$word])));

        return $matches / count($needles);
    }
}
