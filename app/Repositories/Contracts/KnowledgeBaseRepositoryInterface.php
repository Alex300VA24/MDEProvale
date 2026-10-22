<?php

namespace App\Repositories\Contracts;

use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use Illuminate\Support\Collection;

/**
 * Documentos cargados a la base de conocimiento y sus fragmentos indexados.
 */
interface KnowledgeBaseRepositoryInterface
{
    public function recentDocuments(int $limit): Collection;

    public function findByHash(string $hash): ?KnowledgeBaseDocument;

    public function createDocument(array $data): KnowledgeBaseDocument;

    /** Actualiza sin pasar por fillable y devuelve el mismo modelo. */
    public function updateDocument(KnowledgeBaseDocument $document, array $attributes): KnowledgeBaseDocument;

    public function freshDocument(KnowledgeBaseDocument $document): KnowledgeBaseDocument;

    public function deleteDocument(KnowledgeBaseDocument $document): void;

    public function deleteChunks(KnowledgeBaseDocument $document): void;

    public function createChunk(array $data): KnowledgeBaseDocumentChunk;

    public function chunksWithoutEmbedding(int $documentId): int;

    /**
     * Fragmentos de documentos indexados, con su documento cargado.
     *
     * @param  array<int, int>  $documentIds  prefiltro opcional por documento
     * @param  array<int, string>|null  $keywords  prefiltro léxico; null no filtra
     */
    public function searchChunks(array $documentIds, ?array $keywords, int $limit): Collection;
}
