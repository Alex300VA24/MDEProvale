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
        'detail_product_id',
        'type_transaction_id',
        'document_number',
        'adjustment',
        'transaction_date',
        'product_name',
        'uom_title',
    ];

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class);
    }

    // Acceso al producto actual a través del lote
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            DetailProduct::class,
            'id',               // FK en detail_products
            'id',               // FK en products
            'detail_product_id',// local key en transactions
            'product_id'        // local key en detail_products
        );
    }

    public function typeTransaction()
    {
        return $this->belongsTo(TypeTransaction::class);
    }
}
