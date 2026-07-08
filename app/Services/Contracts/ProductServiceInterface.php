<?php

namespace App\Services\Contracts;

interface ProductServiceInterface
{
    public function generateReport(string $tipo, array $filters = []): array;
}