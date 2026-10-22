<?php

namespace App\Console\Commands;

use App\Console\Support\CommandReporter;
use App\Services\Maintenance\RagReindexService;
use Illuminate\Console\Command;

class ReindexRagEmbeddings extends Command
{
    protected $signature = 'rag:reindex {--only-missing : Solo chunks sin embedding} {--limit=200 : Tope de chunks por corrida}';

    protected $description = 'Regenera embeddings normalizados y reporta documentos que quedaron solo con búsqueda léxica';

    public function handle(RagReindexService $reindex): int
    {
        if ($reindex->provider() !== 'google') {
            $this->warn('AI_PROVIDER='.$reindex->provider().': embedText retornará null y el RAG seguirá en modo léxico. Use AI_PROVIDER=google para vectores reales.');
        }

        $updated = $reindex->reindexChunks(
            (bool) $this->option('only-missing'),
            max(1, (int) $this->option('limit')),
            new CommandReporter($this),
        );

        $this->info("Chunks reindexados: {$updated}.");

        $this->table(['pipeline', 'docs INDEXADO', 'chunks sin embedding'], $reindex->summary());

        // Migración diferida de binarios legacy a disco (sin romper descargas).
        $this->info('Binarios migrados a disco: '.$reindex->migrateBinaries().'.');

        return self::SUCCESS;
    }
}
