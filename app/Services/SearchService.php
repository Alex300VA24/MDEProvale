<?php

namespace App\Services;

use App\Repositories\Contracts\PartnerRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;

/**
 * Búsqueda ligera de personas y socios para los desplegables Select2.
 */
class SearchService
{
    private const MIN_TERM_LENGTH = 2;
    private const MAX_LIMIT = 100;

    public function __construct(
        private PersonRepositoryInterface $people,
        private PartnerRepositoryInterface $partners
    ) {
    }

    /** @return array<int, array{id:int, text:string}> */
    public function people(string $query, int $limit): array
    {
        return $this->options($this->people->searchByTerms($this->terms($query), $this->clampLimit($limit)));
    }

    /** @return array<int, array{id:int, text:string}> */
    public function partners(string $query, int $limit): array
    {
        return $this->options($this->partners->searchByTerms($this->terms($query), $this->clampLimit($limit)));
    }

    private function terms(string $query): array
    {
        $query = trim($query);

        return mb_strlen($query) >= self::MIN_TERM_LENGTH
            ? preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY)
            : [];
    }

    private function clampLimit(int $limit): int
    {
        return min($limit, self::MAX_LIMIT);
    }

    private function options($rows): array
    {
        return $rows->map(fn ($p) => [
            'id' => $p->id,
            'text' => $p->names . ' ' . $p->father_lastname . ' ' . ($p->mother_lastname ?? '') . ' (' . ($p->dni ?? 'S/DNI') . ')',
        ])->all();
    }
}
