<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'weight',
        'height',
        'hmg',
        'date_begin',
        'date_end',
        'type_benefit_id',
        'beneficiary_id',
        'state_id',
        'reason_disqualification_id',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiarie::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function reasonDisqualification()
    {
        return $this->belongsTo(ReasonDisqualification::class);
    }

    public function typeBenefit()
    {
        return $this->belongsTo(TypeBenefit::class);
    }

    public function obstetricData()
    {
        return $this->hasOne(ObstetricData::class);
    }
}
