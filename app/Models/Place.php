<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title'
    ];

    public function placeSectors()
    {
        return $this->hasMany(PlaceSector::class);
    }
}
