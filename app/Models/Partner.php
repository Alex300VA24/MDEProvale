<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'created_at',
        'updated_at',
    ];

    public function people()
    {
        return $this->belongsTo(People::class, 'person_id');
    }

    public function association()
    {
        return $this->belongsTo(Association::class, 'association_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function directives()
    {
        return $this->hasMany(Directive::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiarie::class);
    }

    public function getNameAttribute()
    {
        return $this->people ? $this->people->names . ' ' . $this->people->father_lastname . ' ' . $this->people->mother_lastname : 'Sin nombre';
    }
}
