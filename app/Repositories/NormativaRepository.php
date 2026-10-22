<?php

namespace App\Repositories;

use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use App\Repositories\Contracts\NormativaRepositoryInterface;
use Illuminate\Support\Collection;

class NormativaRepository implements NormativaRepositoryInterface
{
    public function relevantDocuments(int $limit): Collection
    {
        return NormativaDocument::query()
            ->where('relevancia_pvl', true)
            ->latest('fecha_documento')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function relevantIndexedCount(): int
    {
        return NormativaDocument::query()
            ->where('relevancia_pvl', true)
            ->where('index_status', 'INDEXADO')
            ->count();
    }

    public function lastRelevantUpdate(): mixed
    {
        return NormativaDocument::query()
            ->where('relevancia_pvl', true)
            ->max('updated_at');
    }

    public function findOrFail(int $id): NormativaDocument
    {
        return NormativaDocument::findOrFail($id);
    }

    public function externalIdExists(int $externalId): bool
    {
        return NormativaDocument::where('external_id', $externalId)->exists();
    }

    public function create(array $data): NormativaDocument
    {
        return NormativaDocument::create($data);
    }

    public function upsertByExternalId(int $externalId, array $attributes): NormativaDocument
    {
        $document = NormativaDocument::query()->firstOrNew(['external_id' => $externalId]);
        $document->fill($attributes)->save();

        return $document;
    }

    public function update(NormativaDocument $document, array $attributes): NormativaDocument
    {
        $document->forceFill($attributes)->save();

        return $document;
    }

    public function freshDocument(NormativaDocument $document): NormativaDocument
    {
        return $document->fresh();
    }

    public function deleteChunks(NormativaDocument $document): void
    {
        $document->chunks()->delete();
    }

    public function createChunk(array $data): NormativaDocumentChunk
    {
        return NormativaDocumentChunk::create($data);
    }

    public function searchChunks(?array $keywords, int $limit): Collection
    {
        $query = NormativaDocumentChunk::query()
            ->select(['id', 'normativa_document_id', 'content', 'embedding', 'page_number', 'chunk_index'])
            ->with('document:id,tipo_documento,numero,periodo,titulo,pdf_url,file_path,index_status')
            ->whereHas('document', fn ($documents) => $documents->where('index_status', 'INDEXADO'))
            ->orderBy('id');

        if ($keywords !== null) {
            $query->where(function ($where) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $where->orWhere('content', 'like', '%'.$keyword.'%');
                }
            });
        }

        return $query->limit($limit)->get();
    }
}
