<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'abbreviation',
        'min_age',
        'max_age',
        'priority',
    ];

    public function histories()
    {
        return $this->hasMany(BeneficiaryHistory::class);
    }
}
