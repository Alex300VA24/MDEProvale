<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReasonDisqualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'observation',
    ];

    public function histories()
    {
        return $this->hasMany(BeneficiaryHistory::class);
    }
}
