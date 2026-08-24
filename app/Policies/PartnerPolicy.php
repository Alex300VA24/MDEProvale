<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function view(User $user, Partner $partner): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule('socios-beneficiarios');
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->canEditModule('socios-beneficiarios');
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->canDeleteModule('socios-beneficiarios');
    }
}
