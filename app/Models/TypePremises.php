<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypePremises extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    public function associations()
    {
        return $this->hasMany(Association::class);
    }
}
