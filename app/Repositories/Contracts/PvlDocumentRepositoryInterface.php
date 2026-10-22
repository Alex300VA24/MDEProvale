<?php

namespace App\Repositories\Contracts;

use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use Illuminate\Support\Collection;

/**
 * Fragmentos indexados de los documentos de respaldo PVL.
 */
interface PvlDocumentRepositoryInterface
{
    /** Actualiza sin pasar por fillable y devuelve el mismo modelo. */
    public function updateDocument(PvlDocument $document, array $attributes): PvlDocument;

    public function deleteChunks(PvlDocument $document): void;

    public function createChunk(array $data): PvlDocumentChunk;

    /**
     * Fragmentos de documentos indexados del periodo, con su documento cargado.
     *
     * @param  array<int, string>  $types
     * @param  array<int, string>|null  $keywords  prefiltro léxico; null no filtra
     */
    public function searchChunks(string $period, array $types, ?array $keywords, int $limit): Collection;

    /**
     * Documentos indexados del periodo (id, file_hash, updated_at) por id.
     *
     * @param  array<int, string>  $types
     */
    public function indexedDocuments(string $period, array $types): Collection;
}
