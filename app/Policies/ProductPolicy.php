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
        return $user->hasModuleAccess('productos');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasModuleAccess('productos') && $user->isAdmin();
    }
}