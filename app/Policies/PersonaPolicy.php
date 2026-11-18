<?php

namespace App\Policies;

use App\Models\People;
use App\Models\User;
use App\Support\ModuleCatalog;

class PersonaPolicy
{
    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }

    public function update(User $user, People $person): bool
    {
        return $user->canEditModule(ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS);
    }
}
