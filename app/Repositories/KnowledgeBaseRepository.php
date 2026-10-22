<?php

namespace App\Repositories;

use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Repositories\Contracts\KnowledgeBaseRepositoryInterface;
use Illuminate\Support\Collection;

class KnowledgeBaseRepository implements KnowledgeBaseRepositoryInterface
{
    public function recentDocuments(int $limit): Collection
    {
        return KnowledgeBaseDocument::query()->latest('id')->limit($limit)->get();
    }

    public function findByHash(string $hash): ?KnowledgeBaseDocument
    {
        return KnowledgeBaseDocument::query()->where('file_hash', $hash)->first();
    }

    public function createDocument(array $data): KnowledgeBaseDocument
    {
        return KnowledgeBaseDocument::create($data);
    }

    public function updateDocument(KnowledgeBaseDocument $document, array $attributes): KnowledgeBaseDocument
    {
        $document->forceFill($attributes)->save();

        return $document;
    }

    public function freshDocument(KnowledgeBaseDocument $document): KnowledgeBaseDocument
    {
        return $document->fresh();
    }

    public function deleteDocument(KnowledgeBaseDocument $document): void
    {
        $document->delete();
    }

    public function deleteChunks(KnowledgeBaseDocument $document): void
    {
        $document->chunks()->delete();
    }

    public function createChunk(array $data): KnowledgeBaseDocumentChunk
    {
        return KnowledgeBaseDocumentChunk::create($data);
    }

    public function chunksWithoutEmbedding(int $documentId): int
    {
        // embedding JSON null o 'null' serializado.
        return KnowledgeBaseDocumentChunk::query()
            ->where('kb_document_id', $documentId)
            ->where(function ($where) {
                $where->whereNull('embedding')->orWhere('embedding', 'like', '%null%');
            })
            ->count();
    }

    public function searchChunks(array $documentIds, ?array $keywords, int $limit): Collection
    {
        $query = KnowledgeBaseDocumentChunk::query()
            ->select(['id', 'kb_document_id', 'content', 'embedding', 'page_number', 'chunk_index'])
            ->with('document:id,title,file_name,index_status')
            ->whereHas('document', function ($documents) use ($documentIds) {
                $documents->where('index_status', 'INDEXADO');
                if ($documentIds !== []) {
                    $documents->whereIn('id', $documentIds);
                }
            })
            ->orderBy('id');

        if ($keywords !== null) {
            // Sin vectores: prefiltrado SQL por keywords para no traer 1000 filas.
            $query->where(function ($where) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $where->orWhere('content', 'like', '%'.$keyword.'%');
                }
            });
        }

        return $query->limit($limit)->get();
    }
}
