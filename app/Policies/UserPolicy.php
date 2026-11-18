<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ModuleCatalog;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::SISTEMA);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::SISTEMA);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::SISTEMA);
    }

    public function update(User $user, User $model): bool
    {
        return $user->canEditModule(ModuleCatalog::SISTEMA);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->canDeleteModule(ModuleCatalog::SISTEMA) && $user->id !== $model->id;
    }
}
