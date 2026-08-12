<?php

namespace App\Policies;

use App\Models\Rol;
use App\Models\User;

class RolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function update(User $user, Rol $rol): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function delete(User $user, Rol $rol): bool
    {
        return $user->hasModuleAccess('sistema') && $user->isAdmin();
    }
}
