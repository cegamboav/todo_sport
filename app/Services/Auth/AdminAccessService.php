<?php

namespace App\Services\Auth;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\User;

class AdminAccessService
{
    /** @var list<UserRole> */
    private const OPERATIONAL_ROLES = [
        UserRole::Staff,
        UserRole::Mesa,
        UserRole::Corner,
    ];

    public function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function isMesa(User $user): bool
    {
        return $user->role === UserRole::Mesa;
    }

    public function isStaff(User $user): bool
    {
        return $user->role === UserRole::Staff;
    }

    public function isProfessor(User $user): bool
    {
        return $user->role === UserRole::Professor;
    }

    public function isCornerJudge(User $user): bool
    {
        return $user->role === UserRole::Corner;
    }

    public function isOperationalRole(User $user): bool
    {
        return in_array($user->role, self::OPERATIONAL_ROLES, true);
    }

    public function userHasStaffAssignmentOnEvent(User $user, Event $event): bool
    {
        if (! $this->isStaff($user)) {
            return false;
        }

        return EventStaff::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Assignment on an event in an active staff phase (registration or operational).
     */
    public function activeEventAssignment(User $user): ?EventStaff
    {
        if (! $this->isOperationalRole($user)) {
            return null;
        }

        $statuses = match ($user->role) {
            UserRole::Staff => array_map(
                fn (EventStatus $status) => $status->value,
                EventStatus::staffAssignmentStatuses(),
            ),
            UserRole::Mesa, UserRole::Corner => [EventStatus::Operational->value],
            default => [],
        };

        if ($statuses === []) {
            return null;
        }

        return EventStaff::query()
            ->with('event:id,name,status')
            ->where('user_id', $user->id)
            ->whereHas('event', fn ($query) => $query->whereIn('status', $statuses))
            ->orderByDesc('assigned_at')
            ->first();
    }

    public function canAccessAdminShell(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->isStaff($user) || $this->isMesa($user)) {
            return $this->activeEventAssignment($user) !== null;
        }

        return false;
    }

    public function canAccessMastersModule(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isStaff($user) && $this->activeEventAssignment($user) !== null;
    }

    public function canAccessEventsModule(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isStaff($user) && $this->activeEventAssignment($user) !== null;
    }

    public function canManageEvent(User $user, Event $event): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isStaff($user)
            && $this->userHasStaffAssignmentOnEvent($user, $event)
            && $event->status->allowsStaffOperationalAccess();
    }

    /**
     * Configuración del torneo: participantes, modalidades, staff, categorías.
     */
    public function canAccessEventAdminWorkspace(User $user, Event $event): bool
    {
        return $this->canManageEvent($user, $event);
    }

    /**
     * Operación en vivo: caja, check-in, pagos, validación (S2C+).
     */
    public function canAccessEventOperationsWorkspace(User $user, Event $event): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->userHasStaffAssignmentOnEvent($user, $event)) {
            return false;
        }

        if ($this->isStaff($user) || $this->isMesa($user)) {
            return in_array($event->status, [
                EventStatus::RegistrationOpen,
                EventStatus::RegistrationClosed,
                EventStatus::Operational,
            ], true);
        }

        return false;
    }

    public function canAccessDashboard(User $user): bool
    {
        return $this->canAccessAdminShell($user);
    }

    public function canAccessRingsModule(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isMesa($user) && $this->activeEventAssignment($user) !== null;
    }

    public function canLoginMainPanel(User $user): bool
    {
        return $this->canAccessAdminShell($user);
    }

    public function canLoginJudgeApp(User $user): bool
    {
        return $this->isCornerJudge($user) && $this->activeEventAssignment($user) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedAccessState(User $user): array
    {
        $assignment = $this->activeEventAssignment($user);

        return [
            'can_access_masters' => $this->canAccessMastersModule($user),
            'can_access_events' => $this->canAccessEventsModule($user),
            'can_access_rings' => $this->canAccessRingsModule($user),
            'can_access_dashboard' => $this->canAccessDashboard($user),
            'active_event_staff' => $assignment ? [
                'event_id' => $assignment->event_id,
                'event_name' => $assignment->event?->name,
                'event_status' => $assignment->event?->status?->value ?? $assignment->event?->status,
            ] : null,
        ];
    }

    public function assignUserToEvent(Event $event, User $user, ?User $assignedBy = null): EventStaff
    {
        return EventStaff::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ],
            [
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
            ],
        );
    }

    /** @deprecated Use assignUserToEvent() */
    public function assignStaffToEvent(Event $event, User $staffUser, ?User $assignedBy = null): EventStaff
    {
        return $this->assignUserToEvent($event, $staffUser, $assignedBy);
    }
}
