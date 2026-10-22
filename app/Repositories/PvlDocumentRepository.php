<?php

namespace App\Repositories;

use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Repositories\Contracts\PvlDocumentRepositoryInterface;
use Illuminate\Support\Collection;

class PvlDocumentRepository implements PvlDocumentRepositoryInterface
{
    public function updateDocument(PvlDocument $document, array $attributes): PvlDocument
    {
        $document->forceFill($attributes)->save();

        return $document;
    }

    public function deleteChunks(PvlDocument $document): void
    {
        $document->chunks()->delete();
    }

    public function createChunk(array $data): PvlDocumentChunk
    {
        return PvlDocumentChunk::create($data);
    }

    public function searchChunks(string $period, array $types, ?array $keywords, int $limit): Collection
    {
        $query = PvlDocumentChunk::query()
            ->select(['id', 'pvl_document_id', 'content', 'embedding', 'page_number', 'chunk_index', 'metadata'])
            ->with('document:id,document_type,period,product_id,provider_reference,file_name,file_hash,index_status')
            ->whereHas('document', fn ($documents) => $documents
                ->where('period', $period)
                ->where('index_status', 'INDEXADO')
                ->whereIn('document_type', $types))
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

    public function indexedDocuments(string $period, array $types): Collection
    {
        return PvlDocument::query()
            ->where('period', $period)
            ->where('index_status', 'INDEXADO')
            ->whereIn('document_type', $types)
            ->orderBy('id')
            ->get(['id', 'file_hash', 'updated_at']);
    }
}
