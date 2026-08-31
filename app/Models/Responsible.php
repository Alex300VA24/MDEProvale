<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsible extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'type',
        'active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public const TYPE_LABELS = [
        'chief' => 'Subgerente de Programas Sociales',
        'storekeeper' => 'Encargado de PROVALE',
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'person_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /** Filtra por nombre, apellidos o DNI de la persona asociada. */
    public function scopeSearchPerson(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->whereHas('person', fn (Builder $person) => $person->searchIdentity($search));
    }
}
