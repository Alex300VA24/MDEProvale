<?php

namespace App\Repositories;

use App\Models\Transaction;
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
            ->with(['detailProduct:id,product_id,quantity,unit_price,start_date,end_date', 'detailProduct.product:id,title,abbreviation', 'typeTransaction:id,title'])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('product_name', 'like', "{$v}%"))
            ->when($filters['type_transaction_id'] ?? null, fn($q, $v) => $q->where('type_transaction_id', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}