<?php

namespace App\Policies;

use App\Models\Pecosa;
use App\Models\User;

class PecosaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function update(User $user, Pecosa $pecosa): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function delete(User $user, Pecosa $pecosa): bool
    {
        return $user->hasModuleAccess('productos') && $user->isAdmin();
    }
}