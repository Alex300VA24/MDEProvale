<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use App\Support\ModuleCatalog;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::SISTEMA);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::SISTEMA);
    }

    public function update(User $user, Module $module): bool
    {
        return $user->canEditModule(ModuleCatalog::SISTEMA);
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->canDeleteModule(ModuleCatalog::SISTEMA);
    }
}
