<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\EventCombo;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Enums\AuditSeverity;
use Illuminate\Support\Facades\DB;

class EventComboService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $modalityIds
     */
    public function create(Event $event, array $data, array $modalityIds, User $actor): EventCombo
    {
        return DB::transaction(function () use ($event, $data, $modalityIds, $actor) {
            $combo = $event->combos()->create([
                'name' => $data['name'],
                'price' => $data['price'],
                'enabled' => $data['enabled'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $combo->modalities()->sync($modalityIds);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.combo_created',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Combo creado: {$combo->name}",
            );

            return $combo->load('modalities:id,code,name');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $modalityIds
     */
    public function update(EventCombo $combo, array $data, array $modalityIds, User $actor): EventCombo
    {
        return DB::transaction(function () use ($combo, $data, $modalityIds, $actor) {
            $combo->update([
                'name' => $data['name'],
                'price' => $data['price'],
                'enabled' => $data['enabled'] ?? $combo->enabled,
                'sort_order' => $data['sort_order'] ?? $combo->sort_order,
            ]);

            $combo->modalities()->sync($modalityIds);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.combo_updated',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $combo->event_id,
                summary: "Combo actualizado: {$combo->name}",
            );

            return $combo->fresh(['modalities:id,code,name']);
        });
    }

    public function delete(EventCombo $combo, User $actor): void
    {
        DB::transaction(function () use ($combo, $actor) {
            $eventId = $combo->event_id;
            $name = $combo->name;
            $combo->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'event.combo_deleted',
                severity: AuditSeverity::Warning,
                entityType: 'event',
                entityId: $eventId,
                summary: "Combo eliminado: {$name}",
            );
        });
    }
}
