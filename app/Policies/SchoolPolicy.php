<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;
use App\Policies\Concerns\ChecksMastersAccess;

class SchoolPolicy
{
    use ChecksMastersAccess;

    public function viewAny(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function view(User $user, School $school): bool
    {
        return $this->canManageMasters($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function update(User $user, School $school): bool
    {
        return $this->canManageMasters($user);
    }

    public function delete(User $user, School $school): bool
    {
        return $this->canManageMasters($user);
    }

    public function restore(User $user, School $school): bool
    {
        return $this->canManageMasters($user);
    }
}
