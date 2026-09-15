<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Beneficiarie extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'partner_id',
        'relationship_id',
    ];

    public function scopeActiveDuring(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function (Builder $beneficiaries) use ($startDate, $endDate) {
            $beneficiaries->whereDoesntHave('histories')
                ->orWhereHas('histories', function (Builder $history) use ($startDate, $endDate) {
                    $history->whereDate('date_begin', '<=', $endDate)
                        ->where(function (Builder $dates) use ($startDate) {
                            $dates->whereNull('date_end')
                                ->orWhereDate('date_end', '>=', $startDate);
                        });
                });
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(Relationship::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BeneficiaryHistory::class, 'beneficiary_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }

    public function getNameAttribute(): string
    {
        return $this->person ? $this->person->names . ' ' . $this->person->father_lastname . ' ' . $this->person->mother_lastname : 'Sin nombre';
    }
}
