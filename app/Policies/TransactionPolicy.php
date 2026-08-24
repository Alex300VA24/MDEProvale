<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('movimientos');
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule('movimientos');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->canEditModule('movimientos');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->canDeleteModule('movimientos');
    }
}
