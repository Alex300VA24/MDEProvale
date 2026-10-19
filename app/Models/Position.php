<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    public const COMMITTEE_TITLES = [
        'PRESIDENTA',
        'VICE PRESIDENTA',
        'SECRETARIA',
        'TESORERA',
        'COORDINADORA',
        'ALMACENERA',
        'VOCAL',
    ];

    protected $fillable = [
        'title',
    ];

    public function directives()
    {
        return $this->hasMany(Directive::class);
    }
}
