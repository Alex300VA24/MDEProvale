<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Services\AssistantAiService;
use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Console\Command;

class ReindexRagEmbeddings extends Command
{
    protected $signature = 'rag:reindex {--only-missing : Solo chunks sin embedding} {--limit=200 : Tope de chunks por corrida}';

    protected $description = 'Regenera embeddings normalizados y reporta documentos que quedaron solo con búsqueda léxica';

    public function handle(AssistantAiService $ai): int
    {
        if ($ai->provider() !== 'google') {
            $this->warn('AI_PROVIDER='.$ai->provider().': embedText retornará null y el RAG seguirá en modo léxico. Use AI_PROVIDER=google para vectores reales.');
        }

        $onlyMissing = (bool) $this->option('only-missing');
        $limit = max(1, (int) $this->option('limit'));
        $updated = 0;

        $updated += $this->reindexChunks(
            KnowledgeBaseDocumentChunk::query()->with('document'),
            $ai, $onlyMissing, $limit - $updated, 'kb'
        );

        if ($updated < $limit) {
            $updated += $this->reindexChunks(
                PvlDocumentChunk::query()->with('document'),
                $ai, $onlyMissing, $limit - $updated, 'pvl'
            );
        }

        if ($updated < $limit) {
            $updated += $this->reindexChunks(
                NormativaDocumentChunk::query()->with('document'),
                $ai, $onlyMissing, $limit - $updated, 'normativa'
            );
        }

        $this->info("Chunks reindexados: {$updated}.");

        $this->table(
            ['pipeline', 'docs INDEXADO', 'chunks sin embedding'],
            [
                ['kb', KnowledgeBaseDocument::where('index_status', 'INDEXADO')->count(), $this->countMissing(KnowledgeBaseDocumentChunk::query())],
                ['pvl', PvlDocument::where('index_status', 'INDEXADO')->count(), $this->countMissing(PvlDocumentChunk::query())],
                ['normativa', NormativaDocument::where('index_status', 'INDEXADO')->count(), $this->countMissing(NormativaDocumentChunk::query())],
            ]
        );

        // Migración diferida de binarios legacy a disco (sin romper descargas).
        $migrated = $this->migrateBinaries();
        $this->info("Binarios migrados a disco: {$migrated}.");

        return self::SUCCESS;
    }

    private function reindexChunks($query, AssistantAiService $ai, bool $onlyMissing, int $budget, string $pipeline): int
    {
        if ($budget <= 0) {
            return 0;
        }

        if ($onlyMissing) {
            $query->where(function ($where) {
                $where->whereNull('embedding')->orWhere('embedding', 'like', '%null%');
            });
        }

        $chunks = $query->limit($budget)->get();
        $count = 0;

        foreach ($chunks as $chunk) {
            $embedding = $ai->embedText($chunk->content, 'RETRIEVAL_DOCUMENT');
            if ($embedding === null) {
                continue;
            }

            // Normalizar a norma unitaria.
            $norm = sqrt(array_sum(array_map(static fn ($v) => ((float) $v) ** 2, $embedding)));
            if ($norm > 0) {
                $embedding = array_map(static fn ($v) => ((float) $v) / $norm, $embedding);
            }

            $chunk->forceFill(['embedding' => $embedding])->save();
            $count++;
        }

        if ($count > 0) {
            $this->info("{$pipeline}: {$count} embeddings regenerados.");
        }

        return $count;
    }

    private function countMissing($query): int
    {
        return (clone $query)
            ->where(function ($where) {
                $where->whereNull('embedding')->orWhere('embedding', 'like', '%null%');
            })
            ->count();
    }

    private function migrateBinaries(): int
    {
        $migrated = 0;

        foreach (KnowledgeBaseDocument::whereNull('file_path')->whereNotNull('file_data')->limit(50)->get() as $document) {
            $binary = DocumentBinaryStorage::get(null, $document->file_data);
            if ($binary === null) {
                continue;
            }
            $document->forceFill([
                'file_path' => DocumentBinaryStorage::put('rag/kb', $document->file_hash, $binary),
                'file_data' => null,
            ])->save();
            $migrated++;
        }

        foreach (PvlDocument::whereNull('file_path')->whereNotNull('file_data')->limit(50)->get() as $document) {
            // PvlDocument usa cast encrypted: el acceso ya retorna el binario codificado legacy.
            $raw = $document->getAttributes()['file_data'] ?? null;
            $binary = DocumentBinaryStorage::get(null, is_string($raw) ? $raw : null)
                ?? DocumentBinaryStorage::get(null, $document->file_data);
            if ($binary === null) {
                continue;
            }
            $document->forceFill([
                'file_path' => DocumentBinaryStorage::put('rag/pvl', $document->file_hash, $binary),
                'file_data' => null,
            ])->save();
            $migrated++;
        }

        return $migrated;
    }
}
