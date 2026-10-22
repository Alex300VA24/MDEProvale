<?php

namespace App\Services;

use App\Exceptions\SistemaException;
use App\Models\User;
use App\Repositories\Contracts\RolRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

/**
 * Administración de cuentas de usuario.
 */
class UserService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RolRepositoryInterface $roles
    ) {
    }

    public function list(?string $search, mixed $rolId, mixed $stateId, int $perPage): LengthAwarePaginator
    {
        return $this->users->paginate($search ? trim($search) : null, $rolId, $stateId, $perPage);
    }

    /** Catálogos para los filtros y el formulario de usuarios. */
    public function formOptions(): array
    {
        return [
            'roles' => $this->roles->activeOptions(),
            'estados' => $this->users->administrativeStates(),
        ];
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return $this->users->create($data);
    }

    /** Autorregistro: cuenta activa con el rol base. */
    public function register(array $data): User
    {
        return $this->users->create([
            'names' => $data['names'],
            'father_surname' => $data['father_surname'],
            'mother_surname' => $data['mother_surname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'dni' => $data['dni'],
            'cui' => $data['cui'],
            'state_id' => 1,
            'rol_id' => 1,
            'password' => Hash::make($data['password']),
        ]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->users->findByUsername($username);
    }

    public function update(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->users->update($user, $data);
    }

    public function delete(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw new SistemaException('No puedes eliminar tu propio usuario.', 403);
        }

        $this->users->delete($user);
    }

    /** Restablece la contraseña al DNI del usuario. */
    public function resetPassword(User $user): void
    {
        $this->users->update($user, ['password' => Hash::make($user->dni)]);
    }
}
