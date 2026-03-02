<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObstetricData extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_last_menstrual_period',
        'date_estimated_parturition',
        'date_parturition',
        'date_end_breastfeeding',
        'beneficiary_history_id',
    ];

    public function history()
    {
        return $this->belongsTo(BeneficiaryHistory::class);
    }
}
