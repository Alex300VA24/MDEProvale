<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    public function person()
    {
        return $this->belongsTo(People::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }

    public function histories()
    {
        return $this->hasMany(BeneficiaryHistory::class, 'beneficiary_id');
    }

    public function getNameAttribute()
    {
        return $this->person ? $this->person->names . ' ' . $this->person->father_lastname . ' ' . $this->person->mother_lastname : 'Sin nombre';
    }
}
