<?php

namespace App\Repositories\Contracts;

use App\Models\Module;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Módulos del sistema, sus íconos y los permisos efectivos por usuario.
 */
interface ModuleRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator;

    public function allOrdered(): Collection;

    public function activeIcons(): Collection;

    /** @param  int[]  $ids @return int[] ids que existen */
    public function existingIds(array $ids): array;

    public function create(array $data): Module;

    public function update(Module $module, array $data): Module;

    public function delete(Module $module): void;

    public function rolesCount(Module $module): int;

    /** Módulos activos con permisos efectivos del usuario (administrador: todos). */
    public function permissionsFor(User $user): Collection;
}
