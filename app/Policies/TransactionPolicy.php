<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->hasModuleAccess('productos') && $user->isAdmin();
    }
}