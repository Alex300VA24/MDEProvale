<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
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
        'parent_id' => 'integer',
    ];

    public function rols()
    {
        return $this->belongsToMany(Rol::class, 'module_rol')
            ->withPivot('can_view', 'can_create', 'can_edit', 'can_delete')
            ->withTimestamps();
    }

    /**
     * Permisos efectivos por slug, listos para el menú y las comprobaciones de acceso.
     *
     * Reglas del árbol de módulos:
     * - Los permisos se guardan solo en las hojas (módulos sin submódulos).
     * - Un submódulo deja de estar disponible si su módulo padre está inactivo.
     * - Un grupo no guarda permisos: queda visible si el rol ve al menos un submódulo,
     *   y sus banderas son la unión de las de sus submódulos.
     *
     * @param  bool  $grantAll  true para el administrador: todo módulo activo con todos los permisos
     * @return \Illuminate\Support\Collection<string, object> keyed by slug, ordenado como el menú
     */
    public static function effectivePermissions(?int $rolId, bool $grantAll = false): Collection
    {
        $all = DB::table('modules')->get()->keyBy('id');
        $groupIds = $all->pluck('parent_id')->filter()->unique()->flip();
        $granted = ($grantAll || ! $rolId)
            ? collect()
            : DB::table('module_rol')->where('rol_id', $rolId)->get()->keyBy('module_id');

        $isAvailable = fn (object $m): bool => (bool) $m->is_active
            && (! $m->parent_id || (bool) ($all[$m->parent_id]->is_active ?? false));

        $row = function (object $m, bool $view, bool $create, bool $edit, bool $delete) use ($all, $groupIds): object {
            $parent = $m->parent_id ? ($all[$m->parent_id] ?? null) : null;

            return (object) [
                'slug' => $m->slug,
                'name' => $m->name,
                'description' => $m->description,
                'icon' => $m->icon,
                'route' => $m->route,
                'order' => (int) $m->order,
                'parent_slug' => $parent?->slug,
                'is_group' => $groupIds->has($m->id),
                // Un submódulo se ordena dentro de su grupo; el resto, por su propio orden.
                'sort_order' => $parent ? (int) $parent->order * 100 + (int) $m->order : (int) $m->order * 100,
                'can_view' => $view,
                'can_create' => $view && $create,
                'can_edit' => $view && $edit,
                'can_delete' => $view && $delete,
            ];
        };

        $rows = collect();

        foreach ($all as $module) {
            if (! $isAvailable($module) || $groupIds->has($module->id)) {
                continue;
            }

            if ($grantAll) {
                $rows->put($module->slug, $row($module, true, true, true, true));
            } elseif ($permission = $granted->get($module->id)) {
                $rows->put($module->slug, $row(
                    $module,
                    (bool) $permission->can_view,
                    (bool) $permission->can_create,
                    (bool) $permission->can_edit,
                    (bool) $permission->can_delete
                ));
            }
        }

        foreach ($groupIds->keys() as $groupId) {
            $group = $all->get($groupId);
            if (! $group || ! $isAvailable($group)) {
                continue;
            }

            $children = $rows->filter(fn (object $r) => $r->parent_slug === $group->slug && $r->can_view);
            if ($children->isEmpty()) {
                continue;
            }

            $rows->put($group->slug, $row(
                $group,
                true,
                $children->contains('can_create', true),
                $children->contains('can_edit', true),
                $children->contains('can_delete', true)
            ));
        }

        return $rows->sortBy('sort_order')->values()->keyBy('slug');
    }

    /** Módulo grupo al que pertenece este submódulo (null en módulos de primer nivel). */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Submódulos de este módulo, en el orden del menú. */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }
}
