<?php

namespace App\Repositories\Contracts;

use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use Illuminate\Support\Collection;

/**
 * Normas municipales descubiertas en el portal y sus fragmentos indexados.
 */
interface NormativaRepositoryInterface
{
    /** Normas relevantes para el PVL, las más recientes primero. */
    public function relevantDocuments(int $limit): Collection;

    public function relevantIndexedCount(): int;

    /** @return string|null última actualización de una norma relevante */
    public function lastRelevantUpdate(): mixed;

    public function findOrFail(int $id): NormativaDocument;

    public function externalIdExists(int $externalId): bool;

    public function create(array $data): NormativaDocument;

    /** Crea o actualiza (con fillable) la norma identificada por su id externo. */
    public function upsertByExternalId(int $externalId, array $attributes): NormativaDocument;

    /** Actualiza sin pasar por fillable y devuelve el mismo modelo. */
    public function update(NormativaDocument $document, array $attributes): NormativaDocument;

    public function freshDocument(NormativaDocument $document): NormativaDocument;

    public function deleteChunks(NormativaDocument $document): void;

    public function createChunk(array $data): NormativaDocumentChunk;

    /**
     * @param  array<int, string>|null  $keywords  prefiltro léxico; null no filtra
     */
    public function searchChunks(?array $keywords, int $limit): Collection;
}
