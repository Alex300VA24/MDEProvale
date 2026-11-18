<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
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
        'must_change_password',
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
        'must_change_password' => 'boolean',
    ];

    /** Busca por identidad completa aunque nombres y apellidos estén separados. */
    public function scopeSearchIdentity(Builder $query, ?string $search): Builder
    {
        $terms = preg_split('/\s+/u', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($terms)) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($terms) {
            foreach ($terms as $term) {
                $pattern = '%' . addcslashes($term, '\\%_') . '%';

                $outer->where(function (Builder $termQuery) use ($pattern) {
                    $termQuery->where('names', 'like', $pattern)
                        ->orWhere('father_surname', 'like', $pattern)
                        ->orWhere('mother_surname', 'like', $pattern)
                        ->orWhere('username', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern)
                        ->orWhere('dni', 'like', $pattern);
                });
            }
        });
    }

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

public function isPresidentPortalUser(): bool
    {
        return $this->rol
            && $this->rol->is_active
            && mb_strtolower(trim($this->rol->title)) === mb_strtolower(Rol::PRESIDENT);
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    public function forcePasswordChange(): void
    {
        $this->forceFill(['must_change_password' => true])->save();
    }

    /**
     * Per-request cache of module permissions.
     * Loaded once (Module::effectivePermissions), then reused for all permission checks.
     * Key: module slug, Value: object with can_view, can_create, can_edit, can_delete
     */
    protected $modulePermissionsCache = null;

    /**
     * Load all module permissions for this user's role.
     * Caches the result per-request so subsequent calls are free.
     */
    protected function getModulePermissions()
    {
        if ($this->modulePermissionsCache !== null) {
            return $this->modulePermissionsCache;
        }


        if (!$this->rol || !$this->rol->is_active) {
            return $this->modulePermissionsCache = collect();
        }

        // Permisos efectivos por slug: hojas con sus banderas y grupos derivados de sus submódulos.
        $permissions = Module::effectivePermissions((int) $this->rol_id);

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
        return $this->canModule($moduleSlug, 'view');
    }

    public function canAccessModule($moduleSlug)
    {
        return $this->canModule($moduleSlug, 'view');
    }

    public function canCreateModule($moduleSlug)
    {
        return $this->canModule($moduleSlug, 'create');
    }

    public function canEditModule($moduleSlug)
    {
        return $this->canModule($moduleSlug, 'edit');
    }

    public function canDeleteModule($moduleSlug)
    {
        return $this->canModule($moduleSlug, 'delete');
    }

    /**
     * Comprueba una acción (view|create|edit|delete) sobre un módulo o submódulo.
     * Un módulo grupo (p. ej. "comites-resoluciones") se considera accesible/editable
     * si lo es al menos uno de sus submódulos. El administrador activo accede a todo.
     */
    public function canModule(string $moduleSlug, string $ability = 'view'): bool
    {
        if ($this->isAdmin() && $this->rol?->is_active) {
            return true;
        }

        $permission = $this->getModulePermissions()->get($moduleSlug);
        if (! $permission || ! $permission->can_view) {
            return false;
        }

        return match ($ability) {
            'create' => (bool) $permission->can_create,
            'edit' => (bool) $permission->can_edit,
            'delete' => (bool) $permission->can_delete,
            default => true,
        };
    }
}
