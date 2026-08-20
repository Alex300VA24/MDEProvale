<?php

namespace App\Policies;

use App\Models\Racion;
use App\Models\User;

class RacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('responsables-raciones');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('responsables-raciones');
    }

    public function update(User $user, Racion $racion): bool
    {
        return $user->hasModuleAccess('responsables-raciones');
    }

    public function delete(User $user, Racion $racion): bool
    {
        return $user->hasModuleAccess('responsables-raciones') && $user->isAdmin();
    }
}
