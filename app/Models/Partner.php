<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_begin',
        'date_end',
        'observations',
        'state_id',
        'person_id',
        'association_id',
        'position_id',
        'marital_status',
        'education_level',
        'occupation',
        'children_count',
        'is_pregnant',
        'is_lactating',
        'spouse_occupation',
        'spouse_education_level',
        'family_income',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_pregnant' => 'boolean',
        'is_lactating' => 'boolean',
        'children_count' => 'integer',
        'family_income' => 'decimal:2',
    ];

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'person_id');
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class, 'association_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function directives(): HasMany
    {
        return $this->hasMany(Directive::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiarie::class);
    }

    public function rosterPeriods(): HasMany
    {
        return $this->hasMany(PartnerRosterPeriod::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }

    public function getNameAttribute(): string
    {
        return $this->people ? $this->people->names . ' ' . $this->people->father_lastname . ' ' . $this->people->mother_lastname : 'Sin nombre';
    }
}
