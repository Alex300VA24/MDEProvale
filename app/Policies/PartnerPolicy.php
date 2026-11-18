<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;
use App\Support\ModuleCatalog;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }

    public function view(User $user, Partner $partner): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->canEditModule(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->canDeleteModule(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }
}
