<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObstetricData extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_last_menstrual_period',
        'date_estimated_parturition',
        'date_parturition',
        'date_end_breastfeeding',
        'beneficiary_history_id',
        'child_beneficiary_id',
    ];

    protected $casts = [
        'date_last_menstrual_period' => 'date:Y-m-d',
        'date_estimated_parturition' => 'date:Y-m-d',
        'date_parturition' => 'date:Y-m-d',
        'date_end_breastfeeding' => 'date:Y-m-d',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryHistory::class, 'beneficiary_history_id');
    }

    public function childBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiarie::class, 'child_beneficiary_id');
    }
}
