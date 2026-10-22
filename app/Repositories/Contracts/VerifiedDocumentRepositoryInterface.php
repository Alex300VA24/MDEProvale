<?php

namespace App\Repositories\Contracts;

use App\Models\VerifiedDocument;

interface VerifiedDocumentRepositoryInterface
{
    public function create(array $data): VerifiedDocument;

    public function update(VerifiedDocument $document, array $data): VerifiedDocument;

    public function delete(VerifiedDocument $document): void;

    public function findByTokenOrFail(string $token): VerifiedDocument;
}
