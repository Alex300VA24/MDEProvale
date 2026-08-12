<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'detail_product_id',
        'pecosa_id',
        'transaction_id',
        'quantity',
        'observation',
    ];

    public function detailProduct()
    {
        return $this->belongsTo(DetailProduct::class);
    }

    public function pecosa()
    {
        return $this->belongsTo(Pecosa::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function getStockForDetailProduct($detailProductId)
    {
        $detailProduct = DetailProduct::find($detailProductId);
        if (!$detailProduct) {
            return 0;
        }

        $totalIn = $detailProduct->quantity;
        $totalOut = self::where('detail_product_id', $detailProductId)->sum('quantity');

        return $totalIn - $totalOut;
    }

    public static function getCurrentStockForProduct($productId)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get();

        $totalStock = 0;
        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            $out = $detail->used_quantity ?? 0;
            $totalStock += ($in - $out);
        }

        return $totalStock;
    }
}
