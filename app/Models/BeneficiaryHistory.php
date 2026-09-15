<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BeneficiaryHistory extends Model
{
    use HasFactory;

    protected $table = 'beneficiary_histories';

    protected $fillable = [
        'weight',
        'height',
        'hmg',
        'date_begin',
        'date_end',
        'type_benefit_id',
        'relationship_id',
        'beneficiary_id',
        'state_id',
        'reason_disqualification_id',
        'is_malnourished',
        'is_disabled',
    ];

    protected $casts = [
        'is_malnourished' => 'boolean',
        'is_disabled' => 'boolean',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'hmg' => 'decimal:2',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiarie::class, 'beneficiary_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function reasonDisqualification(): BelongsTo
    {
        return $this->belongsTo(ReasonDisqualification::class);
    }

    public function typeBenefit(): BelongsTo
    {
        return $this->belongsTo(TypeBenefit::class);
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(Relationship::class);
    }

    public function obstetricData(): HasOne
    {
        return $this->hasOne(ObstetricData::class);
    }
}
