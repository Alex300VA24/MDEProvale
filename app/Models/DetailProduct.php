<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_price',
        'quantity',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function getAvailableStockAttribute()
    {
        $totalOut = $this->stocks()->sum('quantity');
        return $this->quantity - $totalOut;
    }

    public function isActive()
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }
}