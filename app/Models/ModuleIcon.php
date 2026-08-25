<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleIcon extends Model
{
    protected $fillable = [
        'name',
        'class_name',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
