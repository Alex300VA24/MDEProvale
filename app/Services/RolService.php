<?php

namespace App\Services;

use App\Exceptions\SistemaException;
use App\Models\Rol;
use App\Repositories\Contracts\ModuleRepositoryInterface;
use App\Repositories\Contracts\RolRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Administración de roles y de sus permisos por módulo.
 */
class RolService
{
    /** Id del rol base del sistema (Administrador). */
    private const ADMIN_ID = 1;

    public function __construct(
        private RolRepositoryInterface $roles,
        private ModuleRepositoryInterface $modules
    ) {
    }

    public function list(int $perPage): LengthAwarePaginator
    {
        return $this->roles->paginateWithModules($perPage);
    }

    public function create(array $data): Rol
    {
        $permissions = $this->extractPermissions($data);

        $rol = $this->roles->create($data);
        $this->roles->replaceModules($rol, $permissions);

        return $this->roles->withModulesAndUsers($rol);
    }

    public function update(Rol $rol, array $data): Rol
    {
        if ($rol->id === self::ADMIN_ID && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw new SistemaException('No se puede inactivar el rol Administrador.');
        }

        $permissions = $this->extractPermissions($data);

        $this->roles->update($rol, $data);
        $this->roles->replaceModules($rol, $permissions);

        return $this->roles->withModulesAndUsers($rol);
    }

    public function delete(Rol $rol): void
    {
        if ($rol->id === self::ADMIN_ID) {
            throw new SistemaException('No se puede eliminar el rol Administrador: es el rol base del sistema.');
        }

        if ($this->roles->usersCount($rol) > 0) {
            throw new SistemaException('No se puede eliminar el rol porque tiene usuarios asociados.');
        }

        $this->roles->delete($rol);
    }

    /**
     * Separa y normaliza los permisos por módulo del payload. Editar, crear y
     * eliminar solo aplican si el rol puede ver el módulo.
     *
     * @return array<int, array<string,bool>>
     */
    private function extractPermissions(array &$data): array
    {
        $modules = $data['modules'] ?? [];
        unset($data['modules']);

        $requestedIds = array_map('intval', array_keys($modules));
        if (array_diff($requestedIds, $this->modules->existingIds($requestedIds)) !== []) {
            throw ValidationException::withMessages(['modules' => 'Uno o más módulos seleccionados no existen.']);
        }

        $permissions = [];
        foreach ($modules as $moduleId => $flags) {
            $canView = (bool) ($flags['can_view'] ?? false);
            $permissions[$moduleId] = [
                'can_view' => $canView,
                'can_create' => $canView && (bool) ($flags['can_create'] ?? false),
                'can_edit' => $canView && (bool) ($flags['can_edit'] ?? false),
                'can_delete' => $canView && (bool) ($flags['can_delete'] ?? false),
            ];
        }

        return $permissions;
    }
}
