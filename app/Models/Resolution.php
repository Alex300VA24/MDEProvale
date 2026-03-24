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
