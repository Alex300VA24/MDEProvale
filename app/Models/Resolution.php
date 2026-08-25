<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'document',
        'date_document',
        'date_start',
        'date_end',
        'file',
        'state_id',
    ];

    protected $casts = [
        'date_document' => 'date:Y-m-d',
        'date_start'    => 'date:Y-m-d',
        'date_end'      => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::saving(function (Resolution $resolution) {
            if (!$resolution->date_end) {
                return;
            }

            $abbreviation = $resolution->date_end->copy()->startOfDay()->lt(now()->startOfDay())
                ? State::EXPIRED
                : State::CURRENT;
            $stateId = State::idFor($abbreviation);

            if ($stateId) {
                $resolution->state_id = $stateId;
            }
        });
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function directives()
    {
        return $this->hasMany(Directive::class);
    }

    public function associations()
    {
        return $this->belongsToMany(Association::class, 'resolution_associations');
    }

    public function primaryAssociations()
    {
        return $this->hasMany(Association::class, 'resolution_id');
    }

    public function getAllAssociations(): \Illuminate\Support\Collection
    {
        return collect($this->primaryAssociations)
            ->concat($this->associations)
            ->unique('id')
            ->values();
    }

    public function scopeActivas($query)
    {
        return $query->where('date_end', '>=', now());
    }

    // En Resolucion.php
    public function associationsHistory()
    {
        return $this->belongsToMany(Association::class, 'resolution_associations');
    }

}
