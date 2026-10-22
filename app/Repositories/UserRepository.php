<?php

namespace App\Repositories;

use App\Models\State;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(?string $search, mixed $rolId, mixed $stateId, int $perPage): LengthAwarePaginator
    {
        $query = User::with(['rol', 'state'])->orderBy('names');

        if ($search) {
            $query->searchIdentity($search);
        }

        if ($rolId) {
            $query->where('rol_id', $rolId);
        }

        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create($data)->load(['rol', 'state']);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(['rol', 'state']);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByDni(string $dni): ?User
    {
        return User::firstWhere('dni', $dni);
    }

    public function findByUsername(string $username): ?User
    {
        return User::with('rol')->where('username', $username)->first();
    }

    public function usernameTaken(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    public function administrativeStates(): Collection
    {
        return State::administrative()->orderBy('title')->get(['id', 'title', 'abbreviation']);
    }

    public function activeState(): ?State
    {
        return State::whereIn('abbreviation', [State::ACTIVE, 'A'])->orderBy('id')->first();
    }
}
