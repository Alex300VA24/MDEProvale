<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'document',
        'date_document',
        'date_start',
        'date_end',
        'association_id',
        'state_id',
    ];

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function directives()
    {
        return $this->hasMany(Directive::class);
    }
}
