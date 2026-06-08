<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Services\Auth\AdminAccessService;

trait ChecksMastersAccess
{
    protected function canManageMasters(User $user): bool
    {
        return app(AdminAccessService::class)->canAccessMastersModule($user);
    }
}
