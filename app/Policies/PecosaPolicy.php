<?php

namespace App\Policies;

use App\Models\Pecosa;
use App\Models\User;
use App\Support\ModuleCatalog;

class PecosaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::PECOSAS);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::PECOSAS);
    }

    public function update(User $user, Pecosa $pecosa): bool
    {
        return $user->canEditModule(ModuleCatalog::PECOSAS);
    }

    public function delete(User $user, Pecosa $pecosa): bool
    {
        return $user->canDeleteModule(ModuleCatalog::PECOSAS);
    }
}
