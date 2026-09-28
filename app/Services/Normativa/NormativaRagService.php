<?php

namespace App\Services\Normativa;

use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use App\Services\AssistantAiService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Smalot\PdfParser\Parser;

class NormativaRagService
{
    public function __construct(private AssistantAiService $ai)
    {
    }

    public function indexDocument(NormativaDocument $document, string $pdfBinary): void
    {
        $document->forceFill(['index_status' => 'INDEXANDO', 'index_error' => null])->save();

        try {
            $text = $this->extractText($pdfBinary);
            if (trim($text) === '') {
                throw new RuntimeException('No se encontró texto legible en el PDF.');
            }

            $chunks = $this->chunkText($text);
            if ($chunks === []) {
                throw new RuntimeException('No fue posible fragmentar el documento.');
            }

            DB::transaction(function () use ($document, $chunks) {
                $document->chunks()->delete();

                foreach ($chunks as $index => $content) {
                    NormativaDocumentChunk::create([
                        'normativa_document_id' => $document->id,
                        'page_number' => null,
                        'chunk_index' => $index + 1,
                        'content' => $content,
                        'embedding' => $this->ai->embedText($content, 'RETRIEVAL_DOCUMENT'),
                        'metadata' => [
                            'document_id' => $document->id,
                            'tipo_documento' => $document->tipo_documento,
                            'numero' => $document->numero,
                            'periodo' => $document->periodo,
                            'chunk' => $index + 1,
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

    private function extractText(string $binary): string
    {
        try {
            $pdf = (new Parser())->parseContent($binary);
            $text = trim($pdf->getText());
            if ($text !== '') {
                return $text;
            }
        } catch (\Throwable) {
            // Se intenta extracción vía IA a continuación.
        }

        return (string) ($this->ai->extractDocumentText('application/pdf', $binary) ?? '');
    }

    /** @return array<int, string> */
    private function chunkText(string $text): array
    {
        $normalized = trim(preg_replace('/[\t ]+/u', ' ', preg_replace('/\R{3,}/u', "\n\n", $text)) ?? $text);
        $size = max(400, (int) config('normativa.chunk_size', 1200));
        $overlap = min($size - 100, max(0, (int) config('normativa.chunk_overlap', 200)));
        $length = mb_strlen($normalized);
        $chunks = [];

        for ($offset = 0; $offset < $length; $offset += ($size - $overlap)) {
            $chunk = trim(mb_substr($normalized, $offset, $size));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return array_slice($chunks, 0, 60);
    }
}
