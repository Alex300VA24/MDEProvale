<?php

namespace App\Policies;

use App\Models\Racion;
use App\Models\User;
use App\Support\ModuleCatalog;

class RacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::RESPONSABLES_RACIONES);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::RESPONSABLES_RACIONES);
    }

    public function update(User $user, Racion $racion): bool
    {
        return $user->canEditModule(ModuleCatalog::RESPONSABLES_RACIONES);
    }

    public function delete(User $user, Racion $racion): bool
    {
        return $user->canDeleteModule(ModuleCatalog::RESPONSABLES_RACIONES);
    }
}
