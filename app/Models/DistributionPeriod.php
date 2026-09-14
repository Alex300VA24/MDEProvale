<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'service_days',
        'milk_grams_per_beneficiary',
        'oat_grams_per_beneficiary',
        'milk_can_grams',
        'oat_bag_grams',
        'milk_cans_per_box',
        'oat_kg_per_sack',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'service_days' => 'integer',
        'milk_grams_per_beneficiary' => 'decimal:3',
        'oat_grams_per_beneficiary' => 'decimal:3',
        'milk_can_grams' => 'decimal:3',
        'oat_bag_grams' => 'decimal:3',
        'milk_cans_per_box' => 'integer',
        'oat_kg_per_sack' => 'integer',
    ];

    public function assignments()
    {
        return $this->hasMany(DistributionAssignment::class);
    }
}
