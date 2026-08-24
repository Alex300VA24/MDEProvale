<?php

namespace App\Policies;

use App\Models\Resolution;
use App\Models\User;

class ResolutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('club-madres');
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule('club-madres');
    }

    public function update(User $user, Resolution $resolution): bool
    {
        return $user->canEditModule('club-madres');
    }

    public function delete(User $user, Resolution $resolution): bool
    {
        return $user->canDeleteModule('club-madres');
    }
}
