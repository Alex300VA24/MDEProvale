<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'years_old',
        'months_old',
        'days_old',
        'address',
        'finca_number',
        'place_sector_id',
    ];

    protected $appends = ['age_formatted'];

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiarie::class);
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
