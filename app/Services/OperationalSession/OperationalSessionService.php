<?php

namespace App\Services\OperationalSession;

use App\Enums\OperationalLockStrength;
use App\Enums\OperationalSessionType;
use App\Models\OperationalSession;
use App\Models\User;
use App\Support\OperationalSessionConflictException;
use Illuminate\Support\Carbon;

class OperationalSessionService
{
    public const HEARTBEAT_SECONDS = 30;

    public const TIMEOUT_SECONDS = 120;

    public function findActive(
        OperationalSessionType $sessionType,
        string $entityType,
        int $entityId,
    ): ?OperationalSession {
        return OperationalSession::query()
            ->with('user:id,username')
            ->where('session_type', $sessionType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereNull('ended_at')
            ->first();
    }

    public function start(
        int $eventId,
        OperationalSessionType $sessionType,
        string $entityType,
        int $entityId,
        User $user,
        ?OperationalLockStrength $lockStrength = null,
    ): OperationalSession {
        $existing = $this->findActive($sessionType, $entityType, $entityId);

        if ($existing !== null && $existing->user_id !== $user->id) {
            throw OperationalSessionConflictException::forSession($existing);
        }

        if ($existing !== null) {
            return $this->heartbeat($existing);
        }

        $now = now();

        return OperationalSession::query()->create([
            'event_id' => $eventId,
            'session_type' => $sessionType,
            'lock_strength' => $lockStrength ?? $sessionType->defaultLockStrength(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $user->id,
            'started_at' => $now,
            'last_heartbeat_at' => $now,
        ]);
    }

    public function heartbeat(OperationalSession $session): OperationalSession
    {
        $session->update(['last_heartbeat_at' => now()]);

        return $session->fresh();
    }

    public function end(OperationalSession $session, string $reason = 'released'): OperationalSession
    {
        $session->update([
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);

        return $session->fresh();
    }

    public function assertSoft(
        OperationalSessionType $sessionType,
        string $entityType,
        int $entityId,
        User $user,
    ): OperationalSession {
        $session = $this->findActive($sessionType, $entityType, $entityId);

        if ($session === null) {
            throw OperationalSessionConflictException::missingSession();
        }

        if ($this->isExpired($session)) {
            $this->end($session, 'timeout');
            throw OperationalSessionConflictException::expiredSession();
        }

        if ($session->user_id !== $user->id) {
            throw OperationalSessionConflictException::forSession($session);
        }

        return $session;
    }

    public function isExpired(OperationalSession $session): bool
    {
        return $session->last_heartbeat_at instanceof Carbon
            && $session->last_heartbeat_at->lt(now()->subSeconds(self::TIMEOUT_SECONDS));
    }
}
