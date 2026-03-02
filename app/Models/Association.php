<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'observation',
        'property_number'
    ];

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function typePremises()
    {
        return $this->belongsTo(TypePremises::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function awards()
    {
        return $this->hasMany(Award::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function pecosas()
    {
        return $this->hasMany(Pecosa::class);
    }


}
