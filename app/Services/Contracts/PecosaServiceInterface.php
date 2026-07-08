<?php

namespace App\Services\Contracts;

use App\Models\Pecosa;

interface PecosaServiceInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10);
    public function createPecosa(array $data): Pecosa;
    public function updatePecosa(int $id, array $data): Pecosa;
    public function generateComprobante(Pecosa $pecosa);
}