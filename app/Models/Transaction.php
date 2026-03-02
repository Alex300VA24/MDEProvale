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
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function typeTransaction()
    {
        return $this->belongsTo(TypeTransaction::class);
    }
}
