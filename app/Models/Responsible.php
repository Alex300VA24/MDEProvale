<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsible extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(People::class, 'person_id');
    }
}