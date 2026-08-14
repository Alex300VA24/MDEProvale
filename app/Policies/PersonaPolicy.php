<?php

namespace App\Policies;

use App\Models\People;
use App\Models\User;

class PersonaPolicy
{
    public function create(User $user): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function update(User $user, People $person): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }
}
