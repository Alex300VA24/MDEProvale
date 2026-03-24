<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPecosa extends Model
{
    use HasFactory;

    protected $fillable = [
        'priority',
        'quantity',
        'delivered_quantity',
        'unit_price',
        'subtotal',
        'detail_product_id',
        'pecosa_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'delivered_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class, 'detail_product_id');
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            DetailProduct::class,
            'id',         // FK en detail_products
            'id',         // FK en products
            'detail_product_id', // local key en detail_pecosas
            'product_id'  // local key en detail_products
        );
    }

    public function pecosa()
    {
        return $this->belongsTo(Pecosa::class);
    }
}
