<?php

namespace App\Repositories;

use App\Models\Module;
use App\Models\ModuleIcon;
use App\Models\User;
use App\Repositories\Contracts\ModuleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ModuleRepository implements ModuleRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Module::orderBy('order')->paginate($perPage);
    }

    public function allOrdered(): Collection
    {
        return Module::orderBy('order')->get();
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

    public function permissionsFor(User $user): Collection
    {
        // Misma lógica que User::getModulePermissions(): un solo JOIN sobre module_rol + modules.
        $columns = ['modules.slug', 'modules.name', 'modules.description', 'modules.icon', 'modules.route', 'modules.order'];

        if ($user->isAdmin()) {
            return DB::table('modules')
                ->where('modules.is_active', true)
                ->orderBy('modules.order')
                ->select(...$columns, ...[
                    DB::raw('1 as can_view'),
                    DB::raw('1 as can_create'),
                    DB::raw('1 as can_edit'),
                    DB::raw('1 as can_delete'),
                ])
                ->get();
        }

        return DB::table('module_rol')
            ->join('modules', 'modules.id', '=', 'module_rol.module_id')
            ->where('module_rol.rol_id', $user->rol_id)
            ->where('modules.is_active', true)
            ->orderBy('modules.order')
            ->select(...$columns, ...[
                'module_rol.can_view',
                'module_rol.can_create',
                'module_rol.can_edit',
                'module_rol.can_delete',
            ])
            ->get();
    }
}
