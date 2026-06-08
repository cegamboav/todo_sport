<?php

namespace App\Policies;

use App\Models\Modality;
use App\Models\User;
use App\Services\Auth\AdminAccessService;

class ModalityPolicy
{
    public function viewAny(User $user): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }

    public function update(User $user, Modality $modality): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }
}
