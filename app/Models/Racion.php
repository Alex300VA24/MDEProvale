<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Racion extends Model
{
    use HasFactory;

    protected $table = 'raciones';

    protected $fillable = [
        'year',
        'racion_hojuelas_gramos',
        'racion_leche_militros',
        'active',
    ];

    protected $casts = [
        'year' => 'integer',
        'racion_hojuelas_gramos' => 'decimal:2',
        'racion_leche_militros' => 'decimal:2',
        'active' => 'boolean',
    ];
}
