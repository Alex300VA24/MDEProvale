<?php

namespace App\Repositories\Contracts;

use App\Models\Rol;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Roles y sus permisos por módulo.
 */
interface RolRepositoryInterface
{
    /** Roles activos para el selector de usuarios (`id`, `title`). */
    public function activeOptions(): Collection;

    public function paginateWithModules(int $perPage): LengthAwarePaginator;

    public function create(array $data): Rol;

    public function update(Rol $rol, array $data): void;

    /** Elimina el rol y sus permisos. */
    public function delete(Rol $rol): void;

    /** @param  array<int, array<string,bool>>  $permissions  permisos por id de módulo */
    public function replaceModules(Rol $rol, array $permissions): void;

    public function usersCount(Rol $rol): int;

    /** Rol recargado con sus módulos y el conteo de usuarios. */
    public function withModulesAndUsers(Rol $rol): Rol;

    public function firstOrCreateByTitle(string $title, array $defaults): Rol;
}
