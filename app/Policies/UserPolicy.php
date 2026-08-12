<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasModuleAccess('sistema') && $user->isAdmin() && $user->id !== $model->id;
    }
}