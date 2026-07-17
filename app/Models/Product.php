<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    private $cachedStock = null;
    private $cachedUnitPrice = null;

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
        $today = now()->toDateString();
        $detailProducts = $this->relationLoaded('detailProducts')
            ? $this->detailProducts
                ->filter(function ($detail) use ($today) {
                    $startDate = $detail->start_date ? $detail->start_date->toDateString() : null;
                    $endDate = $detail->end_date ? $detail->end_date->toDateString() : null;

                    return $startDate && $endDate && $startDate <= $today && $endDate >= $today;
                })
            : $this->detailProducts()
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->withSum('stocks as used_quantity', 'quantity')
                ->get();

        $totalStock = 0;
        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            if (array_key_exists('used_quantity', $detail->getAttributes())) {
                $out = $detail->used_quantity ?? 0;
            } elseif ($detail->relationLoaded('stocks')) {
                $out = $detail->stocks->sum('quantity');
            } else {
                $out = $detail->stocks()->sum('quantity');
            }
            $totalStock += ($in - $out);
        }

        return $totalStock;
    }

    public function flushCache()
    {
        $this->cachedStock = null;
        $this->cachedUnitPrice = null;
    }

    public function getStockAttribute()
    {
        if ($this->cachedStock === null) {
            $this->cachedStock = $this->getAvailableStock();
        }
        return $this->cachedStock;
    }

    public function getUnitPriceAttribute()
    {
        if ($this->cachedUnitPrice !== null) {
            return $this->cachedUnitPrice;
        }

        $today = now()->toDateString();
        $detailProduct = $this->relationLoaded('detailProducts')
            ? $this->detailProducts
                ->filter(function ($detail) use ($today) {
                    $startDate = $detail->start_date ? $detail->start_date->toDateString() : null;
                    $endDate = $detail->end_date ? $detail->end_date->toDateString() : null;

                    return $startDate && $endDate && $startDate <= $today && $endDate >= $today;
                })
                ->sortBy('start_date')
                ->first()
            : DetailProduct::where('product_id', $this->id)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->orderBy('start_date')
                ->first();
        $this->cachedUnitPrice = $detailProduct ? $detailProduct->unit_price : 0;
        return $this->cachedUnitPrice;
    }
}
