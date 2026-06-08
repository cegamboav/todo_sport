<?php

namespace App\Services\Events;

use App\Enums\AuditSeverity;
use App\Enums\EventStatus;
use App\Enums\ParticipantEnrollmentStatus;
use App\Enums\ThirdPlaceMode;
use App\Models\Event;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\EventSetting;
use App\Models\Modality;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    use BuildsEventAuditPayload;

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Event::query()
            ->with('hostSchool:id,name,abbreviation')
            ->orderByDesc('event_date')
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Event
    {
        return DB::transaction(function () use ($data, $actor) {
            $event = Event::query()->create([
                'name' => $data['name'],
                'event_date' => $data['event_date'] ?? null,
                'venue' => $data['venue'] ?? null,
                'host_school_id' => $data['host_school_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => EventStatus::Draft,
            ]);

            EventSetting::query()->create([
                'event_id' => $event->id,
                'third_place_mode' => ThirdPlaceMode::NoBronze,
                'allow_team_forms' => false,
                'bronze_mode' => null,
            ]);

            $this->bootstrapEventModalities($event);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.created',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Evento creado: {$event->name}",
                payloadAfter: $this->eventAuditSnapshot($event),
            );

            return $event->fresh(['hostSchool', 'settings']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data, User $actor): Event
    {
        return DB::transaction(function () use ($event, $data, $actor) {
            $before = $this->eventAuditSnapshot($event);
            $event->update($data);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.updated',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Evento actualizado: {$event->name}",
                payloadBefore: $before,
                payloadAfter: $this->eventAuditSnapshot($event->fresh()),
            );

            return $event->fresh(['hostSchool', 'settings']);
        });
    }

    public function transitionStatus(Event $event, EventStatus $status, User $actor): Event
    {
        return DB::transaction(function () use ($event, $status, $actor) {
            $before = $this->eventAuditSnapshot($event);
            $event->update(['status' => $status]);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.status_changed',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Evento {$event->name}: {$before['status']} → {$status->value}",
                payloadBefore: $before,
                payloadAfter: $this->eventAuditSnapshot($event->fresh()),
            );

            return $event->fresh();
        });
    }

    public function loadHub(Event $event): Event
    {
        return $event->load([
            'hostSchool:id,name,abbreviation',
            'settings',
            'eventModalities.modality:id,code,name',
            'combos.modalities:id,code,name',
            'eventCompetitors' => fn ($query) => $query
                ->where('status', ParticipantEnrollmentStatus::Active)
                ->with([
                    'competitor:id,first_name,last_name,school_id',
                    'competitor.school:id,name,abbreviation',
                    'registrationItems',
                ]),
            'eventStaff.user:id,username,role',
        ]);
    }

    /**
     * @param  list<array{modality_id: int, enabled: bool, price: float|int|string}>  $rows
     */
    public function syncEventModalities(Event $event, array $rows, User $actor): void
    {
        DB::transaction(function () use ($event, $rows, $actor) {
            foreach ($rows as $row) {
                EventModality::query()->updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'modality_id' => $row['modality_id'],
                    ],
                    [
                        'enabled' => (bool) $row['enabled'],
                        'price' => $row['price'],
                    ],
                );
            }

            $this->audit->record(
                actor: $actor,
                eventType: 'event.modalities_synced',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Modalidades del evento {$event->name} actualizadas",
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(Event $event, array $data, User $actor): EventSetting
    {
        return DB::transaction(function () use ($event, $data, $actor) {
            $settings = $event->settings ?? EventSetting::query()->create(['event_id' => $event->id]);
            $settings->update($data);

            $this->audit->record(
                actor: $actor,
                eventType: 'event.settings_updated',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: "Configuración del evento {$event->name} actualizada",
            );

            return $settings->fresh();
        });
    }

    private function bootstrapEventModalities(Event $event): void
    {
        Modality::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->each(function (Modality $modality) use ($event) {
                EventModality::query()->create([
                    'event_id' => $event->id,
                    'modality_id' => $modality->id,
                    'enabled' => false,
                    'price' => 0,
                ]);
            });
    }

    public function assertRegistrationOpen(Event $event): void
    {
        if (! $event->allowsRegistration()) {
            throw ValidationException::withMessages([
                'competitor_id' => 'El evento no acepta inscripciones en este estado. Cambia a «Inscripción abierta».',
            ]);
        }
    }

    /**
     * Agregar participantes / cobros en fases de configuración o torneo activo (no finalizado).
     */
    public function assertCanEnrollParticipants(Event $event): void
    {
        $allowed = [
            EventStatus::Draft,
            EventStatus::RegistrationOpen,
            EventStatus::RegistrationClosed,
            EventStatus::Operational,
        ];

        if (! in_array($event->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'competitor_id' => "No se pueden inscribir participantes con el evento en estado «{$event->status->label()}».",
            ]);
        }
    }

    public function assertCanManageRegistrations(Event $event): void
    {
        $this->assertCanEnrollParticipants($event);
    }

    public function assertCanWithdrawParticipant(Event $event, EventCompetitor $participant): void
    {
        if ($participant->event_id !== $event->id) {
            abort(404);
        }

        if ($event->status === EventStatus::Archived) {
            throw ValidationException::withMessages([
                'participant' => 'No se puede desinscribir en un evento archivado.',
            ]);
        }

        if ($event->status === EventStatus::Finished) {
            throw ValidationException::withMessages([
                'participant' => 'No se puede desinscribir: el evento ya finalizó.',
            ]);
        }

        if ($participant->status === ParticipantEnrollmentStatus::Withdrawn) {
            throw ValidationException::withMessages([
                'participant' => 'Este competidor ya fue desinscrito del evento.',
            ]);
        }

        // Future S2B/S2C: assert no active bracket assignments or fight results.
    }
}
