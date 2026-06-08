<?php

namespace App\Services\Events;

use App\Models\Event;

trait BuildsEventAuditPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function eventAuditSnapshot(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'status' => $event->status?->value ?? $event->status,
            'event_date' => $event->event_date?->format('Y-m-d'),
        ];
    }
}
