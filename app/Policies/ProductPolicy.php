<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\ModuleCatalog;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::PRODUCTOS);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::PRODUCTOS);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canEditModule(ModuleCatalog::PRODUCTOS);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canDeleteModule(ModuleCatalog::PRODUCTOS);
    }
}
