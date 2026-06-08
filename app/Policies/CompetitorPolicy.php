<?php

namespace App\Policies;

use App\Models\Competitor;
use App\Models\User;
use App\Policies\Concerns\ChecksMastersAccess;

class CompetitorPolicy
{
    use ChecksMastersAccess;

    public function viewAny(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function view(User $user, Competitor $competitor): bool
    {
        return $this->canManageMasters($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function update(User $user, Competitor $competitor): bool
    {
        return $this->canManageMasters($user);
    }

    public function delete(User $user, Competitor $competitor): bool
    {
        return $this->canManageMasters($user);
    }

    public function restore(User $user, Competitor $competitor): bool
    {
        return $this->canManageMasters($user);
    }
}
