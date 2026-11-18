<?php

namespace App\Services;

use App\Exceptions\SistemaException;
use App\Models\Module;
use App\Models\User;
use App\Repositories\Contracts\ModuleRepositoryInterface;
use App\Support\ModuleCatalog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Administración de módulos del sistema y permisos efectivos por usuario.
 */
class ModuleService
{
    public function __construct(private ModuleRepositoryInterface $modules)
    {
    }

    public function list(int $perPage): LengthAwarePaginator
    {
        return $this->modules->paginate($perPage);
    }

    public function options(): Collection
    {
        return $this->modules->allOrdered();
    }

    public function icons(): Collection
    {
        return $this->modules->activeIcons();
    }

    public function create(array $data): Module
    {
        return $this->modules->create($data);
    }

    public function update(Module $module, array $data): Module
    {
        return $this->modules->update($module, $data);
    }

    public function delete(Module $module): void
    {
        if ($module->slug === ModuleCatalog::SISTEMA) {
            throw new SistemaException('No se puede eliminar el módulo Sistema: es requerido para administrar usuarios, roles y módulos.');
        }

        if (ModuleCatalog::isBuiltIn($module->slug)) {
            throw new SistemaException('No se puede eliminar un módulo del sistema: sus rutas y permisos dependen de él. Desactívalo si no debe mostrarse.');
        }

        if ($this->modules->childrenCount($module) > 0) {
            throw new SistemaException('No se puede eliminar el módulo porque tiene submódulos. Elimina o reasigna primero sus submódulos.');
        }

        if ($this->modules->rolesCount($module) > 0) {
            throw new SistemaException('No se puede eliminar el módulo porque está asignado a uno o más roles.');
        }

        $this->modules->delete($module);
    }

    /** Módulos activos con los permisos efectivos del usuario, para el menú. */
    public function permissionsFor(User $user): Collection
    {
        return $this->modules->permissionsFor($user);
    }
}
