<?php

namespace App\Policies;

use App\Models\Referee;
use App\Models\User;
use App\Policies\Concerns\ChecksMastersAccess;

class RefereePolicy
{
    use ChecksMastersAccess;

    public function viewAny(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function view(User $user, Referee $referee): bool
    {
        return $this->canManageMasters($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMasters($user);
    }

    public function update(User $user, Referee $referee): bool
    {
        return $this->canManageMasters($user);
    }

    public function delete(User $user, Referee $referee): bool
    {
        return $this->canManageMasters($user);
    }

    public function restore(User $user, Referee $referee): bool
    {
        return $this->canManageMasters($user);
    }
}
