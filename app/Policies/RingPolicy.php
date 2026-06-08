<?php

namespace App\Policies;

use App\Models\Ring;
use App\Models\User;
use App\Services\Auth\AdminAccessService;

class RingPolicy
{
    public function viewAny(User $user): bool
    {
        return app(AdminAccessService::class)->canAccessRingsModule($user);
    }

    public function view(User $user, Ring $ring): bool
    {
        return app(AdminAccessService::class)->canAccessRingsModule($user);
    }

    public function create(User $user): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }

    public function update(User $user, Ring $ring): bool
    {
        return app(AdminAccessService::class)->canAccessRingsModule($user);
    }

    public function delete(User $user, Ring $ring): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }
}
