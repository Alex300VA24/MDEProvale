<?php

namespace App\Repositories\Contracts;

use App\Models\State;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Cuentas de usuario y catálogos que las acompañan.
 */
interface UserRepositoryInterface
{
    public function paginate(?string $search, mixed $rolId, mixed $stateId, int $perPage): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): void;

    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByDni(string $dni): ?User;

    public function findByUsername(string $username): ?User;

    public function usernameTaken(string $username): bool;

    /** Estados administrativos (activo/inactivo) para el selector de usuarios. */
    public function administrativeStates(): Collection;

    /** Primer estado "activo" disponible para las cuentas del portal. */
    public function activeState(): ?State;
}
