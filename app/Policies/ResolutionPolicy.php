<?php

namespace App\Policies;

use App\Models\Resolution;
use App\Models\User;
use App\Support\ModuleCatalog;

class ResolutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::RESOLUCIONES);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::RESOLUCIONES);
    }

    public function update(User $user, Resolution $resolution): bool
    {
        return $user->canEditModule(ModuleCatalog::RESOLUCIONES);
    }

    public function delete(User $user, Resolution $resolution): bool
    {
        return $user->canDeleteModule(ModuleCatalog::RESOLUCIONES);
    }
}
