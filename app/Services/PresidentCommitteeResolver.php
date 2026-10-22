<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Directive;
use App\Models\User;
use App\Repositories\Contracts\DirectiveRepositoryInterface;

class PresidentCommitteeResolver
{
    public function __construct(private DirectiveRepositoryInterface $directives)
    {
    }

    public function resolve(User $user): ?Association
    {
        return $this->resolveAssignment($user)?->partner?->association;
    }

    public function resolveAssignment(User $user): ?Directive
    {
        return $this->directives->currentPresidentAssignment((string) $user->dni, now()->toDateString());
    }
}
