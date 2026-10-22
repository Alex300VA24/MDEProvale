<?php

namespace App\Repositories;

use App\Models\VerifiedDocument;
use App\Repositories\Contracts\VerifiedDocumentRepositoryInterface;

class VerifiedDocumentRepository implements VerifiedDocumentRepositoryInterface
{
    public function create(array $data): VerifiedDocument
    {
        return VerifiedDocument::create($data);
    }

    public function update(VerifiedDocument $document, array $data): VerifiedDocument
    {
        $document->update($data);

        return $document->fresh();
    }

    public function delete(VerifiedDocument $document): void
    {
        $document->delete();
    }

    public function findByTokenOrFail(string $token): VerifiedDocument
    {
        return VerifiedDocument::where('token', $token)->firstOrFail();
    }
}
