<?php

namespace App\Policies;

use App\Models\Rol;
use App\Models\User;
use App\Support\ModuleCatalog;

class RolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::SISTEMA);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::SISTEMA);
    }

    public function update(User $user, Rol $rol): bool
    {
        return $user->canEditModule(ModuleCatalog::SISTEMA);
    }

    public function delete(User $user, Rol $rol): bool
    {
        return $user->canDeleteModule(ModuleCatalog::SISTEMA);
    }
}
