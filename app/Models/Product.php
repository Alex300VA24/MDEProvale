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
        'stock',
        'unit_price',
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
}
