<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Support\ModuleCatalog;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess(ModuleCatalog::REGISTRO_INGRESO);
    }

    public function create(User $user): bool
    {
        return $user->canCreateModule(ModuleCatalog::REGISTRO_INGRESO);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->canEditModule(ModuleCatalog::REGISTRO_INGRESO);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->canDeleteModule(ModuleCatalog::REGISTRO_INGRESO);
    }
}
