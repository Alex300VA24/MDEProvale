<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'names',
        'father_surname',
        'mother_surname',
        'username',
        'dni',
        'cui',
        'state_id',
        'rol_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function hasRole($roleId)
    {
        return $this->rol_id === $roleId;
    }

    public function isAdmin()
    {
        return $this->rol_id === 1;
    }

    public function isMainUser()
    {
        return $this->rol_id === 2;
    }

    public function isBasicUser()
    {
        return $this->rol_id === 3;
    }

    public function hasModuleAccess($moduleSlug)
    {
        $module = Module::where('slug', $moduleSlug)->first();
        if (!$module) return false;
        
        $rolModule = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $this->rol_id)
            ->first();
        
        return $rolModule && $rolModule->can_view;
    }

    public function canAccessModule($moduleSlug)
    {
        $module = Module::where('slug', $moduleSlug)->first();
        if (!$module) return false;
        
        $rolModule = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $this->rol_id)
            ->first();
        
        return $rolModule && $rolModule->can_view;
    }

    public function canCreateModule($moduleSlug)
    {
        $module = Module::where('slug', $moduleSlug)->first();
        if (!$module) return false;
        
        $rolModule = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $this->rol_id)
            ->first();
        
        return $rolModule && $rolModule->can_create;
    }

    public function canEditModule($moduleSlug)
    {
        $module = Module::where('slug', $moduleSlug)->first();
        if (!$module) return false;
        
        $rolModule = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $this->rol_id)
            ->first();
        
        return $rolModule && $rolModule->can_edit;
    }

    public function canDeleteModule($moduleSlug)
    {
        $module = Module::where('slug', $moduleSlug)->first();
        if (!$module) return false;
        
        $rolModule = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $this->rol_id)
            ->first();
        
        return $rolModule && $rolModule->can_delete;
    }
}
