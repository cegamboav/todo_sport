<?php

namespace App\Services\Audit;

use App\Enums\AuditSeverity;
use App\Models\AuditEvent;
use App\Models\User;

class AuditService
{
    /**
     * @param  array<string, mixed>|null  $payloadBefore
     * @param  array<string, mixed>|null  $payloadAfter
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        User $actor,
        string $eventType,
        AuditSeverity $severity,
        string $entityType,
        int $entityId,
        string $summary,
        ?int $eventId = null,
        ?array $payloadBefore = null,
        ?array $payloadAfter = null,
        ?array $metadata = null,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'event_id' => $eventId,
            'actor_user_id' => $actor->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event_type' => $eventType,
            'severity' => $severity,
            'summary' => $summary,
            'payload_before' => $payloadBefore,
            'payload_after' => $payloadAfter,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
