<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'min_age',
        'max_age',
        'priority',
        'observation',
    ];

    public function histories()
    {
        return $this->hasMany(BeneficiaryHistory::class);
    }
}
