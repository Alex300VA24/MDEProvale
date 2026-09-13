<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class People extends Model
{
    use HasFactory;

    protected $fillable = [
        'names',
        'father_lastname',
        'mother_lastname',
        'dni',
        'gender',
        'telephone_number',
        'phone_number',
        'birthdate',
        'address',
        'place_sector_id',
        'reniec_photo',
    ];

    protected $hidden = [
        'reniec_photo',
    ];

    protected $casts = [
        'reniec_photo' => 'encrypted',
    ];

    protected $appends = ['age_formatted'];

    /**
     * Busca cada palabra en nombres, apellidos o DNI. Esto permite consultas
     * como "María Quispe", "Quispe María" o solo un apellido sin depender de
     * cómo están separados los datos entre columnas.
     */
    public function scopeSearchIdentity(Builder $query, ?string $search): Builder
    {
        $terms = preg_split('/\s+/u', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($terms)) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($terms) {
            foreach ($terms as $term) {
                $pattern = '%' . addcslashes($term, '\\%_') . '%';

                $outer->where(function (Builder $termQuery) use ($pattern) {
                    $termQuery->where('names', 'like', $pattern)
                        ->orWhere('father_lastname', 'like', $pattern)
                        ->orWhere('mother_lastname', 'like', $pattern)
                        ->orWhere('dni', 'like', $pattern);
                });
            }
        });
    }

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class, 'person_id');
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiarie::class, 'person_id');
    }

    public function getAgeArray(): array
    {
        if (!$this->birthdate) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $birthdate = Carbon::parse($this->birthdate);
        $now = Carbon::now();

        $years = $birthdate->diffInYears($now);
        $birthdate->addYears($years);
        $months = $birthdate->diffInMonths($now);
        $birthdate->addMonths($months);
        $days = $birthdate->diffInDays($now);

        return [
            'years' => $years,
            'months' => $months,
            'days' => $days
        ];
    }

    public function getAgeFormattedAttribute(): string
    {
        $age = $this->getAgeArray();
        
        if ($age['years'] === 0 && $age['months'] === 0) {
            return "{$age['days']} días";
        } elseif ($age['years'] === 0) {
            return "{$age['months']} mes(es) {$age['days']} día(s)";
        } elseif ($age['months'] === 0) {
            return "{$age['years']} año(s)";
        }
        
        return "{$age['years']} año(s) {$age['months']} mes(es) {$age['days']} día(s)";
    }

    public function getYearsOldAttribute(): int
    {
        return $this->getAgeArray()['years'];
    }

    public function getMonthsOldAttribute(): int
    {
        return $this->getAgeArray()['months'];
    }

    public function getDaysOldAttribute(): int
    {
        return $this->getAgeArray()['days'];
    }
}
