<?php

namespace App\Repositories;

use App\Models\Rol;
use App\Repositories\Contracts\RolRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RolRepository implements RolRepositoryInterface
{
    public function activeOptions(): Collection
    {
        return Rol::where('is_active', true)->orderBy('title')->get(['id', 'title']);
    }

    public function paginateWithModules(int $perPage): LengthAwarePaginator
    {
        return Rol::withCount('users')->with('modules')->orderBy('title')->paginate($perPage);
    }

    public function create(array $data): Rol
    {
        return Rol::create($data);
    }

    public function update(Rol $rol, array $data): void
    {
        $rol->update($data);
    }

    public function delete(Rol $rol): void
    {
        $rol->modules()->detach();
        $rol->delete();
    }

    public function replaceModules(Rol $rol, array $permissions): void
    {
        $rol->modules()->detach();

        if ($permissions !== []) {
            $rol->modules()->attach($permissions);
        }
    }

    public function usersCount(Rol $rol): int
    {
        return $rol->users()->count();
    }

    public function withModulesAndUsers(Rol $rol): Rol
    {
        return $rol->fresh(['modules'])->loadCount('users');
    }

    public function firstOrCreateByTitle(string $title, array $defaults): Rol
    {
        return Rol::firstOrCreate(['title' => $title], $defaults);
    }
}
