<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiarie::class);
    }
}
