<?php

namespace App\Repositories;

use App\Models\Module;
use App\Models\ModuleIcon;
use App\Models\User;
use App\Repositories\Contracts\ModuleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ModuleRepository implements ModuleRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return $this->hierarchical()->paginate($perPage);
    }

    public function allOrdered(): Collection
    {
        return $this->hierarchical()->get();
    }

    /**
     * Módulos en orden de menú: cada grupo seguido de sus submódulos.
     * Incluye el padre y la cantidad de submódulos para mostrar la jerarquía.
     */
    private function hierarchical(): Builder
    {
        $grammar = Module::query()->getQuery()->getGrammar();
        $parentOrder = $grammar->wrap('parents.order');
        $ownOrder = $grammar->wrap('modules.order');

        return Module::query()
            ->leftJoin('modules as parents', 'parents.id', '=', 'modules.parent_id')
            ->select('modules.*')
            ->with('parent')
            ->withCount('children')
            ->orderByRaw("COALESCE({$parentOrder}, {$ownOrder})")
            ->orderByRaw('CASE WHEN modules.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('modules.order')
            ->orderBy('modules.id');
    }

    public function activeIcons(): Collection
    {
        return ModuleIcon::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'class_name', 'category']);
    }

    public function existingIds(array $ids): array
    {
        return Module::whereKey($ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function create(array $data): Module
    {
        return Module::create($data);
    }

    public function update(Module $module, array $data): Module
    {
        $module->update($data);

        return $module;
    }

    public function delete(Module $module): void
    {
        $module->delete();
    }

    public function rolesCount(Module $module): int
    {
        return $module->rols()->count();
    }

    public function groupIds(array $ids): array
    {
        return Module::whereKey($ids)->whereHas('children')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function childrenCount(Module $module): int
    {
        return $module->children()->count();
    }

    public function permissionsFor(User $user): Collection
    {
        // Administrador: todos los módulos activos con todos los permisos.
        // Resto: permisos del rol sobre las hojas, más los grupos derivados de sus submódulos.
        return Module::effectivePermissions($user->rol_id, $user->isAdmin())->values();
    }
}
