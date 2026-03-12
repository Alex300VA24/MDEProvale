<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'observation',
        'property_number',
        'state_id',
        'place_sector_id',
        'type_premises_id',
        'resolution_id',
        'president',
    ];

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function typePremises()
    {
        return $this->belongsTo(TypePremises::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }

    // Backward compatibility for existing code that still uses the plural name.
    public function resolutions()
    {
        return $this->resolution();
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function pecosas()
    {
        return $this->hasMany(Pecosa::class);
    }

    public function directives()
    {
        return $this->hasManyThrough(Directive::class, Resolution::class);
    }

    public function hasPresidenta()
    {
        return $this->directives()
            ->whereHas('position', function ($q) {
                $q->where('title', 'like', '%PRESIDENTA%');
            })
            ->whereHas('state', function ($q) {
                $q->where('abbreviation', 'ACTI');
            })
            ->exists();
    }

    public function isHabilitado()
    {
        return $this->state && $this->state->abbreviation === 'ACTI';
    }

    // En Asociacion.php
    public function resolutionsHistory()
    {
        return $this->belongsToMany(Resolution::class, 'resolution_associations');
    }

}
