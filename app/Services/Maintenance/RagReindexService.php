<?php

namespace App\Services\Maintenance;

use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use App\Services\AssistantAiService;
use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Regenera embeddings normalizados de los tres pipelines RAG y migra de forma
 * diferida los binarios legacy a disco.
 */
class RagReindexService
{
    private const PIPELINES = ['kb', 'pvl', 'normativa'];

    public function __construct(
        private AssistantAiService $ai,
        private DataMaintenanceRepositoryInterface $data,
    ) {
    }

    public function provider(): string
    {
        return $this->ai->provider();
    }

    /** Reindexa chunks hasta agotar el tope; devuelve cuántos se regeneraron. */
    public function reindexChunks(bool $onlyMissing, int $limit, ProgressReporter $out): int
    {
        $updated = 0;

        foreach (self::PIPELINES as $pipeline) {
            if ($updated >= $limit) {
                break;
            }

            $updated += $this->reindexPipeline($pipeline, $onlyMissing, $limit - $updated, $out);
        }

        return $updated;
    }

    /** @return array<int, array{string,int,int}> */
    public function summary(): array
    {
        return array_map(
            fn (string $pipeline) => [
                $pipeline,
                $this->data->indexedDocumentCount($pipeline),
                $this->data->chunksMissingEmbeddingCount($pipeline),
            ],
            self::PIPELINES,
        );
    }

    public function migrateBinaries(): int
    {
        $migrated = 0;

        foreach ($this->data->documentsWithLegacyBinary('kb', 50) as $document) {
            $binary = DocumentBinaryStorage::get(null, $document->file_data);
            if ($binary === null) {
                continue;
            }
            $this->data->storeBinaryPath($document, DocumentBinaryStorage::put('rag/kb', $document->file_hash, $binary));
            $migrated++;
        }

        foreach ($this->data->documentsWithLegacyBinary('pvl', 50) as $document) {
            // PvlDocument usa cast encrypted: el acceso ya retorna el binario codificado legacy.
            $raw = $document->getAttributes()['file_data'] ?? null;
            $binary = DocumentBinaryStorage::get(null, is_string($raw) ? $raw : null)
                ?? DocumentBinaryStorage::get(null, $document->file_data);
            if ($binary === null) {
                continue;
            }
            $this->data->storeBinaryPath($document, DocumentBinaryStorage::put('rag/pvl', $document->file_hash, $binary));
            $migrated++;
        }

        return $migrated;
    }

    private function reindexPipeline(string $pipeline, bool $onlyMissing, int $budget, ProgressReporter $out): int
    {
        if ($budget <= 0) {
            return 0;
        }

        /** @var Collection $chunks */
        $chunks = $this->data->chunksForReindex($pipeline, $onlyMissing, $budget);
        $count = 0;

        foreach ($chunks as $chunk) {
            $embedding = $this->ai->embedText($chunk->content, 'RETRIEVAL_DOCUMENT');
            if ($embedding === null) {
                continue;
            }

            // Normalizar a norma unitaria.
            $norm = sqrt(array_sum(array_map(static fn ($v) => ((float) $v) ** 2, $embedding)));
            if ($norm > 0) {
                $embedding = array_map(static fn ($v) => ((float) $v) / $norm, $embedding);
            }

            $this->data->saveEmbedding($chunk, $embedding);
            $count++;
        }

        if ($count > 0) {
            $out->info("{$pipeline}: {$count} embeddings regenerados.");
        }

        return $count;
    }
}
