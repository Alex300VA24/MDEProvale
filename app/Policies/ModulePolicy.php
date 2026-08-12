<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function update(User $user, Module $module): bool
    {
        return $user->hasModuleAccess('sistema');
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->hasModuleAccess('sistema') && $user->isAdmin();
    }
}
