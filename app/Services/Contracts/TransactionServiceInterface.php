<?php

namespace App\Services\Contracts;

use App\Models\Transaction;

interface TransactionServiceInterface
{
    public function searchTransactions(array $filters, int $perPage = 15);
    public function registerIngreso(array $data): Transaction;
    public function registerSalida(array $data): Transaction;
}