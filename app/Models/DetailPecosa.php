<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPecosa extends Model
{
    use HasFactory;
    protected $fillable = [
        'priority',
        'start_date',
        'end_time',
        'quantity',
        'unit_price',
        'product_id',
        'pecosa_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pecosa()
    {
        return $this->belongsTo(Pecosa::class);
    }

}
