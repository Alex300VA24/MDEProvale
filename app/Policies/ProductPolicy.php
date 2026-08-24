<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule('productos');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canEditModule('productos');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canDeleteModule('productos');
    }
}
