<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceSector extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'sector_id',
    ];

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function associations()
    {
        return $this->hasMany(Association::class);
    }

    public function people()
    {
        return $this->hasMany(People::class);
    }
}
