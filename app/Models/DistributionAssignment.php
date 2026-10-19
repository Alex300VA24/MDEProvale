<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionAssignment extends Model
{
    protected $fillable = [
        'distribution_period_id',
        'association_id',
        'route_number',
        'beneficiary_adjustment',
        'scheduled_milk',
        'scheduled_milk_rounded',
        'scheduled_oat',
        'scheduled_oat_rounded',
        'observation',
    ];

    protected $casts = [
        'distribution_period_id' => 'integer',
        'association_id' => 'integer',
        'route_number' => 'integer',
        'beneficiary_adjustment' => 'integer',
        'scheduled_milk' => 'decimal:2',
        'scheduled_milk_rounded' => 'decimal:2',
        'scheduled_oat' => 'decimal:2',
        'scheduled_oat_rounded' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(DistributionPeriod::class, 'distribution_period_id');
    }

    public function association()
    {
        return $this->belongsTo(Association::class);
    }
}
