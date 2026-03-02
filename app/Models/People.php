<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $fillable = [
        'names',
        'father_lastname',
        'mother_lastname',
        'dni',
        'gender',
        'telephone_number',
        'phone_number',
        'birthdate',
        'years_old',
        'months_old',
        'days_old',
        'address',
        'finca_number',
        'place_sector_id',
    ];

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiarie::class);
    }
}
