<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'abbreviation',
        'state_id',
        'uom_id',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function detailPecosas()
    {
        return $this->hasMany(DetailPecosa::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function detailProducts()
    {
        return $this->hasMany(DetailProduct::class);
    }

    public function getAvailableStock()
    {
        $detailProducts = $this->detailProducts()
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        $totalStock = 0;
        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            $out = $detail->stocks()->sum('quantity');
            $totalStock += ($in - $out);
        }

        return $totalStock;
    }

    // Accessors para compatibilidad con vistas existentes
    public function getStockAttribute()
    {
        return $this->getAvailableStock();
    }

    public function getUnitPriceAttribute()
    {
        $detailProduct = DetailProduct::where('product_id', $this->id)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();
        return $detailProduct ? $detailProduct->unit_price : 0;
    }
}
