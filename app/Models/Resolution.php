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
        'state_id',
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
        return $this->hasMany(Association::class);
    }
}
