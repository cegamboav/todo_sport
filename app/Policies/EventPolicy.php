<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\Auth\AdminAccessService;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return app(AdminAccessService::class)->canAccessEventsModule($user);
    }

    public function view(User $user, Event $event): bool
    {
        return app(AdminAccessService::class)->canAccessEventsModule($user);
    }

    public function create(User $user): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }

    public function update(User $user, Event $event): bool
    {
        return app(AdminAccessService::class)->canManageEvent($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return app(AdminAccessService::class)->isAdmin($user)
            && $event->status === EventStatus::Draft;
    }

    public function transitionStatus(User $user, Event $event): bool
    {
        return app(AdminAccessService::class)->isAdmin($user)
            || app(AdminAccessService::class)->canManageEvent($user, $event);
    }

    public function manageStaff(User $user, Event $event): bool
    {
        return app(AdminAccessService::class)->isAdmin($user);
    }
}
