<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function searchTransactions(array $filters, int $perPage = 15): LengthAwarePaginator;
}