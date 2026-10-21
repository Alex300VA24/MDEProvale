<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository extends BaseRepository implements TransactionRepositoryInterface
{
    public function model(): string
    {
        return Transaction::class;
    }

    public function searchTransactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'detail_product_id', 'type_transaction_id', 'quantity', 'unit_price', 'total_price', 'document_number', 'transaction_date', 'product_name', 'uom_title', 'created_at'])
            ->with(['detailProduct:id,product_id,quantity,unit_price,start_date,end_date', 'detailProduct.product:id,title,abbreviation', 'product:products.id,products.title,products.abbreviation', 'typeTransaction:id,title'])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('product_name', 'like', "{$v}%"))
            ->when($filters['type_transaction_id'] ?? null, fn($q, $v) => $q->where('type_transaction_id', $v))
            ->when($filters['year'] ?? null, fn($q, $v) => $q->whereYear('transaction_date', $v))
            ->when($filters['month'] ?? null, fn($q, $v) => $q->whereMonth('transaction_date', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }

    public function freshWithType(Transaction $transaction): Transaction
    {
        $fresh = $transaction->fresh();
        $fresh->load('typeTransaction');

        return $fresh;
    }

    public function findTypeByTitle(string $title): ?TypeTransaction
    {
        return TypeTransaction::whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->first();
    }

    public function findTypeByTitleOrFail(string $title): TypeTransaction
    {
        return TypeTransaction::whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->firstOrFail();
    }

    public function findTypeById(int $id): ?TypeTransaction
    {
        return TypeTransaction::find($id);
    }

    public function deleteByDocumentNumber(string $documentNumber): void
    {
        $this->model->where('document_number', $documentNumber)->delete();
    }

    public function transactionDates(): \Illuminate\Support\Collection
    {
        return $this->model
            ->whereNotNull('transaction_date')
            ->orderByDesc('transaction_date')
            ->pluck('transaction_date');
    }
}
