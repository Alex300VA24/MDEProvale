<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use App\Models\TypeTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function searchTransactions(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Transaction;

    public function update(Model $transaction, array $data): bool;

    public function delete(Model $transaction): bool;

    /** Recarga la transaccion con su tipo (`typeTransaction`). */
    public function freshWithType(Transaction $transaction): Transaction;

    public function findTypeByTitle(string $title): ?TypeTransaction;

    public function findTypeByTitleOrFail(string $title): TypeTransaction;

    public function findTypeById(int $id): ?TypeTransaction;

    public function deleteByDocumentNumber(string $documentNumber): void;

    /** Fechas de todos los movimientos, la más reciente primero. */
    public function transactionDates(): \Illuminate\Support\Collection;
}
