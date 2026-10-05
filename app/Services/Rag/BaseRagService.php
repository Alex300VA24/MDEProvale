<?php

namespace App\Services\Rag;

use App\Services\AssistantAiService;
use Illuminate\Support\Facades\Log;

/**
 * Lógica compartida para los 3 pipelines RAG (KB, PVL, Normativa).
 *
 * MySQL no tiene índice ANN, por lo que la búsqueda sigue siendo
 * brute-force en PHP, pero centralizada, con vectores normalizados,
 * prefiltrado SQL y límites explícitos para no cargar la tabla entera.
 */
abstract class BaseRagService
{
    public function __construct(protected AssistantAiService $ai)
    {
    }

    /**
     * Normaliza un embedding a norma unitaria para que el coseno
     * sea un simple producto punto y sea comparable entre modelos.
     *
     * @param  array<int, float|int>|null  $vector
     * @return array<int, float>|null
     */
    protected function normalizeEmbedding(?array $vector): ?array
    {
        if (! is_array($vector) || $vector === []) {
            return null;
        }

        $norm = 0.0;
        $values = [];
        foreach ($vector as $value) {
            $float = (float) $value;
            if (! is_finite($float)) {
                return null;
            }
            $values[] = $float;
            $norm += $float * $float;
        }

        if ($norm <= 0) {
            return null;
        }

        $norm = sqrt($norm);

        return array_map(static fn (float $v): float => $v / $norm, $values);
    }

    /**
     * Valida dimensión esperada (ej. 768) y loguea mismatch de modelo.
     * Retorna el vector normalizado o null si no es utilizable.
     */
    protected function validatedEmbedding(?array $vector, string $context): ?array
    {
        $normalized = $this->normalizeEmbedding($vector);

        if ($normalized === null) {
            return null;
        }

        $expected = (int) config('rag.embedding_dim', 0);
        if ($expected > 0 && count($normalized) !== $expected) {
            Log::warning('Dimensión de embedding inesperada; se ignora el vector.', [
                'context' => $context,
                'expected' => $expected,
                'actual' => count($normalized),
            ]);

            return null;
        }

        return $normalized;
    }

    protected function warnIfNoEmbedding(?array $queryEmbedding, string $context): void
    {
        if ($queryEmbedding === null) {
            try {
                $provider = $this->ai->provider();
            } catch (\Throwable) {
                $provider = (string) config('services.ai.provider', 'unknown');
            }
            Log::info('RAG sin embeddings: fallback a búsqueda léxica.', [
                'context' => $context,
                'provider' => $provider,
                'hint' => 'Con AI_PROVIDER=groq no hay embeddings. Use AI_PROVIDER=google para búsqueda semántica.',
            ]);
        }
    }

    /** @param array<int, float> $left @param array<int, float> $right */
    protected function cosineSimilarity(array $left, array $right): float
    {
        $length = min(count($left), count($right));
        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        for ($i = 0; $i < $length; $i++) {
            $a = (float) $left[$i];
            $b = (float) $right[$i];
            $dot += $a * $b;
            $leftNorm += $a * $a;
            $rightNorm += $b * $b;
        }

        return ($leftNorm > 0 && $rightNorm > 0)
            ? max(-1.0, min(1.0, $dot / (sqrt($leftNorm) * sqrt($rightNorm))))
            : 0.0;
    }

    protected function lexicalScore(string $query, string $content): float
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

    protected function hybridScore(?array $queryEmbedding, ?array $chunkEmbedding, string $query, string $content): float
    {
        $lexical = $this->lexicalScore($query, $content);

        if ($queryEmbedding === null || $chunkEmbedding === null) {
            return $lexical;
        }

        $semantic = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding);
        // El coseno puede ser negativo; se recorta a [0,1] para el híbrido.
        $semantic = max(0.0, $semantic);

        $semanticWeight = (float) config('rag.semantic_weight', 0.85);
        $lexicalWeight = 1.0 - $semanticWeight;

        return ($semantic * $semanticWeight) + ($lexical * $lexicalWeight);
    }

    /**
     * Extrae palabras clave (>=4 chars) para prefiltrado SQL con LIKE.
     *
     * @return array<int, string>
     */
    protected function keywords(string $query, int $max = 6): array
    {
        preg_match_all('/[\pL\pN]{4,}/u', mb_strtolower($query), $matches);

        return array_slice(array_values(array_unique($matches[0] ?? [])), 0, $max);
    }

    /**
     * Chunking con respeto a límites de oración/párrafo.
     *
     * @return array<int, array{page: int|null, content: string}>
     */
    protected function chunkPages(string $text, int $size, int $overlap, int $maxChunks): array
    {
        $pages = $this->splitPages($text);
        $size = max(400, $size);
        $overlap = min($size - 100, max(0, $overlap));
        $chunks = [];
        $truncated = false;

        foreach ($pages as $page => $content) {
            $content = trim(preg_replace('/[\t ]+/u', ' ', preg_replace('/\R{3,}/u', "\n\n", $content)) ?? $content);
            $length = mb_strlen($content);

            for ($offset = 0; $offset < $length; $offset += ($size - $overlap)) {
                if (count($chunks) >= $maxChunks) {
                    $truncated = true;
                    break 2;
                }

                $raw = mb_substr($content, $offset, $size);
                $chunk = $this->snapToSentence($raw, $offset, $length, $size);
                $chunk = trim($chunk);
                if ($chunk !== '') {
                    $chunks[] = ['page' => $page, 'content' => $chunk];
                }
            }
        }

        if ($truncated) {
            Log::warning('Documento truncado por max_chunks del RAG.', [
                'max_chunks' => $maxChunks,
            ]);
        }

        return $chunks;
    }

    /** @return array<int, string> */
    protected function chunkPlainText(string $text, int $size, int $overlap, int $maxChunks): array
    {
        return array_map(
            static fn (array $chunk): string => $chunk['content'],
            $this->chunkPages($text, $size, $overlap, $maxChunks)
        );
    }

    /** @return array<int, string> */
    protected function splitPages(string $text): array
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

    /**
     * Recorta el chunk al último punto/salto de línea para no partir
     * oraciones, salvo que el chunk sea muy corto.
     */
    private function snapToSentence(string $raw, int $offset, int $totalLength, int $size): string
    {
        if ($offset + $size >= $totalLength) {
            return $raw;
        }

        $breakAt = -1;
        foreach (["\n\n", "\n", '. ', '。', '; '] as $delimiter) {
            $pos = mb_strrpos($raw, $delimiter);
            if ($pos !== false && $pos > (int) ($size * 0.5)) {
                $breakAt = $pos + mb_strlen($delimiter);
                break;
            }
        }

        return $breakAt > 0 ? mb_substr($raw, 0, $breakAt) : $raw;
    }
}
