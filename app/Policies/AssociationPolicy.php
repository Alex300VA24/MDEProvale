<?php

namespace App\Policies;

use App\Models\Association;
use App\Models\User;

class AssociationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('club-madres');
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule('club-madres');
    }

    public function update(User $user, Association $association): bool
    {
        return $user->canEditModule('club-madres');
    }

    public function delete(User $user, Association $association): bool
    {
        return $user->canDeleteModule('club-madres');
    }
}
