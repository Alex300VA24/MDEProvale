<?php

namespace App\Policies;

use App\Models\Association;
use App\Models\User;
use App\Support\ModuleCatalog;

class AssociationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::COMITES);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::COMITES);
    }

    public function update(User $user, Association $association): bool
    {
        return $user->canEditModule(ModuleCatalog::COMITES);
    }

    public function delete(User $user, Association $association): bool
    {
        return $user->canDeleteModule(ModuleCatalog::COMITES);
    }
}
