<?php

namespace App\Policies;

use App\Models\Professor;
use App\Models\User;
use App\Policies\Concerns\ChecksMastersAccess;

class ProfessorPolicy
{
    use ChecksMastersAccess;

    public function viewAny(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function view(User $user, Professor $professor): bool
    {
        return $this->canManageMasters($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function update(User $user, Professor $professor): bool
    {
        return $this->canManageMasters($user);
    }

    public function delete(User $user, Professor $professor): bool
    {
        return $this->canManageMasters($user);
    }

    public function restore(User $user, Professor $professor): bool
    {
        return $this->canManageMasters($user);
    }
}
