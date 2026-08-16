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

    /**
     * Per-request cache of module permissions.
     * Loaded once via a single JOIN query, then reused for all permission checks.
     * Key: module slug, Value: object with can_view, can_create, can_edit, can_delete
     */
    protected $modulePermissionsCache = null;

    /**
     * Load all module permissions for this user's role in a single query.
     * Caches the result per-request so subsequent calls are free.
     */
    protected function getModulePermissions()
    {
        if ($this->modulePermissionsCache !== null) {
            return $this->modulePermissionsCache;
        }

        $permissions = DB::table('module_rol')
            ->join('modules', 'modules.id', '=', 'module_rol.module_id')
            ->where('module_rol.rol_id', $this->rol_id)
            ->select('modules.slug', 'module_rol.can_view', 'module_rol.can_create', 'module_rol.can_edit', 'module_rol.can_delete')
            ->get()
            ->keyBy('slug');

        $this->modulePermissionsCache = $permissions;
        return $permissions;
    }

    /**
     * Clear the cached permissions (useful after role changes).
     */
    public function clearModulePermissionsCache()
    {
        $this->modulePermissionsCache = null;
    }

    public function hasModuleAccess($moduleSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $perms = $this->getModulePermissions();
        return isset($perms[$moduleSlug]) && $perms[$moduleSlug]->can_view;
    }

    public function canAccessModule($moduleSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $perms = $this->getModulePermissions();
        return isset($perms[$moduleSlug]) && $perms[$moduleSlug]->can_view;
    }

    public function canCreateModule($moduleSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $perms = $this->getModulePermissions();
        return isset($perms[$moduleSlug]) && $perms[$moduleSlug]->can_create;
    }

    public function canEditModule($moduleSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $perms = $this->getModulePermissions();
        return isset($perms[$moduleSlug]) && $perms[$moduleSlug]->can_edit;
    }

    public function canDeleteModule($moduleSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }
        $perms = $this->getModulePermissions();
        return isset($perms[$moduleSlug]) && $perms[$moduleSlug]->can_delete;
    }
}
