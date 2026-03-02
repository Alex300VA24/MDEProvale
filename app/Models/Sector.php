<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    public function placeSectors()
    {
        return $this->hasMany(PlaceSector::class);
    }
}
