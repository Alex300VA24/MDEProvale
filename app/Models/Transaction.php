<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'unit_price',
        'total_price',
        'product_id',
        'type_transaction_id',
        'document_number',
        'stock_quantity',
        'stock_unit_price',
        'stock_total_price',
        'adjustment',
        'transaction_date',
        'start_date',
        'end_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function typeTransaction()
    {
        return $this->belongsTo(TypeTransaction::class);
    }

    public function detailProducts()
    {
        return $this->hasMany(DetailProduct::class, 'product_id', 'product_id');
    }
}
