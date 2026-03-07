<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'route',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rols()
    {
        return $this->belongsToMany(Rol::class, 'module_rol')
            ->withPivot('can_view', 'can_create', 'can_edit', 'can_delete')
            ->withTimestamps();
    }
}
