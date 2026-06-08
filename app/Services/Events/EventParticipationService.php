<?php



namespace App\Services\Events;



use App\Enums\MasterStatus;

use App\Enums\ParticipantEnrollmentStatus;

use App\Enums\RegistrationItemStatus;

use App\Enums\RegistrationItemType;

use App\Enums\UserRole;

use App\Models\Event;

use App\Models\EventCombo;

use App\Models\EventCompetitor;

use App\Models\EventModality;

use App\Models\RegistrationItem;

use App\Models\User;

use App\Services\Audit\AuditService;

use App\Services\Masters\CompetitorService;

use App\Enums\AuditSeverity;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



class EventParticipationService

{

    public function __construct(

        private readonly EventService $events,

        private readonly AuditService $audit,

        private readonly CompetitorService $competitors,

    ) {}



    /**

     * @return array{participant: EventCompetitor, created: bool}

     */

    public function addParticipant(Event $event, int $competitorId, ?string $notes, User $actor): array

    {

        $this->events->assertCanEnrollParticipants($event);



        return DB::transaction(function () use ($event, $competitorId, $notes, $actor) {

            $existing = EventCompetitor::query()

                ->where('event_id', $event->id)

                ->where('competitor_id', $competitorId)

                ->first();



            if ($existing !== null) {

                if ($existing->status === ParticipantEnrollmentStatus::Active) {

                    return ['participant' => $existing, 'created' => false, 'reactivated' => false];

                }



                if ($existing->status === ParticipantEnrollmentStatus::Withdrawn) {

                    $existing->update([

                        'status' => ParticipantEnrollmentStatus::Active,

                        'withdrawn_at' => null,

                        'notes' => $notes ?? $existing->notes,

                    ]);



                    $this->audit->record(

                        actor: $actor,

                        eventType: 'event.participant_reactivated',

                        severity: AuditSeverity::Info,

                        entityType: 'event_competitor',

                        entityId: $existing->id,

                        summary: 'Competidor reinscrito en el evento',

                        payloadAfter: ['competitor_id' => $competitorId],

                    );



                    return [

                        'participant' => $existing->fresh([

                            'competitor:id,first_name,last_name',

                            'competitor.school:id,name,abbreviation',

                        ]),

                        'created' => true,

                        'reactivated' => true,

                    ];

                }

            }



            $participant = EventCompetitor::query()->create([

                'event_id' => $event->id,

                'competitor_id' => $competitorId,

                'notes' => $notes,

                'status' => ParticipantEnrollmentStatus::Active,

            ]);



            $this->audit->record(

                actor: $actor,

                eventType: 'event.participant_added',

                severity: AuditSeverity::Info,

                entityType: 'event',

                entityId: $event->id,

                summary: 'Competidor agregado al evento',

                payloadAfter: ['event_competitor_id' => $participant->id, 'competitor_id' => $competitorId],

            );



            return [

                'participant' => $participant->load([

                    'competitor:id,first_name,last_name',

                    'competitor.school:id,name,abbreviation',

                ]),

                'created' => true,

                'reactivated' => false,

            ];

        });

    }



    /**

     * Desinscripción operacional: retira participante, elimina ítems y cobros asociados.

     */

    public function withdrawParticipant(EventCompetitor $participant, User $actor): void

    {

        $participant->loadMissing(['event', 'registrationItems']);

        $event = $participant->event;

        $this->events->assertCanWithdrawParticipant($event, $participant);



        DB::transaction(function () use ($participant, $actor) {

            $itemsSnapshot = [];

            foreach ($participant->registrationItems as $item) {

                $itemsSnapshot[] = [

                    'id' => $item->id,

                    'label' => $item->label,

                    'amount' => (string) $item->amount,

                    'status' => $item->status->value,

                    'is_billable' => $item->is_billable,

                ];

                $item->delete();

            }



            $participant->update([

                'status' => ParticipantEnrollmentStatus::Withdrawn,

                'withdrawn_at' => now(),

            ]);



            $this->audit->record(

                actor: $actor,

                eventType: 'event.participant_withdrawn',

                severity: AuditSeverity::Warning,

                entityType: 'event_competitor',

                entityId: $participant->id,

                summary: 'Competidor desinscrito del evento',

                payloadBefore: ['registration_items' => $itemsSnapshot],

                payloadAfter: [

                    'status' => ParticipantEnrollmentStatus::Withdrawn->value,

                    'items_removed' => count($itemsSnapshot),

                ],

            );

        });

    }



    /**

     * Alta rápida desde el evento: crea competidor en catálogo (inscripción al torneo en quickRegister).

     *

     * @param  array<string, mixed>  $competitorData

     * @return array{competitor_id: int, created_competitor: bool}

     */

    public function quickCreateAndEnroll(Event $event, array $competitorData, User $actor): array

    {

        $this->events->assertCanEnrollParticipants($event);



        return DB::transaction(function () use ($competitorData, $actor) {

            $competitor = $this->competitors->create([

                ...$competitorData,

                'status' => MasterStatus::Active,

            ], $actor);



            return [

                'competitor_id' => $competitor->id,

                'competitor_label' => trim($competitor->first_name.' '.$competitor->last_name),

                'created_competitor' => true,

            ];

        });

    }



    /**

     * Inscripción rápida: agrega al evento y registra modalidades/combos en una sola operación.

     *

     * @param  list<int>  $eventModalityIds

     * @param  list<int>  $eventComboIds

     * @return array{

     *     participant: EventCompetitor,

     *     participant_created: bool,

     *     items_created: int,

     *     items_skipped: int,

     * }

     */

    public function quickRegister(

        Event $event,

        int $competitorId,

        array $eventModalityIds,

        array $eventComboIds,

        ?string $notes,

        User $actor,

    ): array {

        $this->events->assertCanEnrollParticipants($event);



        return DB::transaction(function () use ($event, $competitorId, $eventModalityIds, $eventComboIds, $notes, $actor) {

            $enroll = $this->addParticipant($event, $competitorId, $notes, $actor);

            $participant = $enroll['participant'];

            $itemsCreated = 0;

            $itemsSkipped = 0;



            $modalityIds = array_values(array_unique(array_map('intval', $eventModalityIds)));

            foreach ($modalityIds as $eventModalityId) {

                if ($eventModalityId <= 0) {

                    continue;

                }



                $eventModality = EventModality::query()

                    ->where('event_id', $event->id)

                    ->whereKey($eventModalityId)

                    ->first();



                if ($eventModality === null || ! $eventModality->enabled) {

                    throw ValidationException::withMessages([

                        'event_modality_ids' => 'Modalidad no válida o deshabilitada.',

                    ]);

                }



                if ($this->hasModalityRegistration($participant, $eventModality->id)) {

                    $itemsSkipped++;

                    continue;

                }



                $this->registerModality($participant, $eventModality, $actor, true, false);

                $itemsCreated++;

            }



            $comboIds = array_values(array_unique(array_map('intval', $eventComboIds)));

            foreach ($comboIds as $eventComboId) {

                if ($eventComboId <= 0) {

                    continue;

                }



                $combo = EventCombo::query()

                    ->where('event_id', $event->id)

                    ->whereKey($eventComboId)

                    ->first();



                if ($combo === null || ! $combo->enabled) {

                    throw ValidationException::withMessages([

                        'event_combo_ids' => 'Combo no válido o deshabilitado.',

                    ]);

                }



                if ($this->hasComboRegistration($participant, $combo->id)) {

                    $itemsSkipped++;

                    continue;

                }



                $this->registerCombo($participant, $combo, $actor, true, false);

                $itemsCreated++;

            }



            return [

                'participant' => $participant->fresh([

                    'competitor:id,first_name,last_name',

                    'competitor.school:id,name,abbreviation',

                    'registrationItems',

                ]),

                'participant_created' => $enroll['created'],

                'items_created' => $itemsCreated,

                'items_skipped' => $itemsSkipped,

            ];

        });

    }



    public function registerModality(

        EventCompetitor $participant,

        EventModality $eventModality,

        User $actor,

        bool $isBillable = true,

        bool $allowDuplicateOverride = false,

    ): RegistrationItem {

        $participant->loadMissing('event');

        $eventModality->loadMissing('modality');

        $event = $participant->event;

        $this->events->assertCanManageRegistrations($event);



        if ($eventModality->event_id !== $event->id) {

            throw ValidationException::withMessages(['event_modality_id' => 'Modalidad inválida para este evento.']);

        }



        if (! $eventModality->enabled) {

            throw ValidationException::withMessages(['event_modality_id' => 'Esta modalidad no está habilitada.']);

        }



        $this->assertModalityNotDuplicated(

            $participant,

            $eventModality->id,

            $allowDuplicateOverride,

            $actor,

        );



        $label = $eventModality->modality?->name ?? 'Modalidad';

        $amount = $isBillable ? (string) $eventModality->price : '0';

        $status = $isBillable ? RegistrationItemStatus::Pending : RegistrationItemStatus::Waived;



        return $this->createItem(

            $participant,

            RegistrationItemType::Modality,

            $label,

            $amount,

            $status,

            $actor,

            [

                'event_modality_id' => $eventModality->id,

                'is_billable' => $isBillable,

                'admin_override' => $allowDuplicateOverride && $this->isAdmin($actor),

            ],

        );

    }



    public function registerCombo(

        EventCompetitor $participant,

        EventCombo $combo,

        User $actor,

        bool $isBillable = true,

        bool $allowDuplicateOverride = false,

    ): RegistrationItem {

        $participant->loadMissing('event');

        $event = $participant->event;

        $this->events->assertCanManageRegistrations($event);



        if ($combo->event_id !== $event->id) {

            throw ValidationException::withMessages(['event_combo_id' => 'Combo inválido para este evento.']);

        }



        if (! $combo->enabled) {

            throw ValidationException::withMessages(['event_combo_id' => 'Este combo no está habilitado.']);

        }



        $this->assertComboNotDuplicated($participant, $combo->id, $allowDuplicateOverride, $actor);



        $amount = $isBillable ? (string) $combo->price : '0';

        $status = $isBillable ? RegistrationItemStatus::Pending : RegistrationItemStatus::Waived;



        return $this->createItem(

            $participant,

            RegistrationItemType::Combo,

            $combo->name,

            $amount,

            $status,

            $actor,

            [

                'event_combo_id' => $combo->id,

                'is_billable' => $isBillable,

                'admin_override' => $allowDuplicateOverride && $this->isAdmin($actor),

            ],

        );

    }



    public function updateItemStatus(RegistrationItem $item, RegistrationItemStatus $status, User $actor): RegistrationItem

    {

        return DB::transaction(function () use ($item, $status, $actor) {

            $item->update(['status' => $status]);



            $this->audit->record(

                actor: $actor,

                eventType: 'registration_item.status_changed',

                severity: AuditSeverity::Info,

                entityType: 'registration_item',

                entityId: $item->id,

                summary: "Inscripción {$item->label}: {$status->value}",

            );



            return $item->fresh();

        });

    }

    public function removeRegistrationItem(RegistrationItem $item, User $actor): void
    {
        DB::transaction(function () use ($item, $actor) {
            $label = $item->label;
            $itemId = $item->id;
            $item->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'registration_item.removed',
                severity: AuditSeverity::Warning,
                entityType: 'registration_item',
                entityId: $itemId,
                summary: "Inscripción eliminada: {$label}",
            );
        });
    }

    private function hasModalityRegistration(EventCompetitor $participant, int $eventModalityId): bool

    {

        return RegistrationItem::query()

            ->where('event_competitor_id', $participant->id)

            ->where('event_modality_id', $eventModalityId)

            ->exists();

    }



    private function hasComboRegistration(EventCompetitor $participant, int $eventComboId): bool

    {

        return RegistrationItem::query()

            ->where('event_competitor_id', $participant->id)

            ->where('event_combo_id', $eventComboId)

            ->exists();

    }



    private function assertModalityNotDuplicated(

        EventCompetitor $participant,

        int $eventModalityId,

        bool $allowDuplicateOverride,

        User $actor,

    ): void {

        $exists = RegistrationItem::query()

            ->where('event_competitor_id', $participant->id)

            ->where('event_modality_id', $eventModalityId)

            ->exists();



        if (! $exists) {

            return;

        }



        if ($allowDuplicateOverride && $this->isAdmin($actor)) {

            return;

        }



        throw ValidationException::withMessages([

            'event_modality_id' => 'Este competidor ya tiene inscrita esta modalidad. Solo un administrador puede registrar un duplicado con override explícito.',

        ]);

    }



    private function assertComboNotDuplicated(

        EventCompetitor $participant,

        int $eventComboId,

        bool $allowDuplicateOverride,

        User $actor,

    ): void {

        $exists = RegistrationItem::query()

            ->where('event_competitor_id', $participant->id)

            ->where('event_combo_id', $eventComboId)

            ->exists();



        if (! $exists) {

            return;

        }



        if ($allowDuplicateOverride && $this->isAdmin($actor)) {

            return;

        }



        throw ValidationException::withMessages([

            'event_combo_id' => 'Este competidor ya tiene registrado este combo.',

        ]);

    }



    private function isAdmin(User $actor): bool

    {

        return $actor->role === UserRole::Admin;

    }



    /**

     * @param  array<string, mixed>  $foreignKeys

     */

    private function createItem(

        EventCompetitor $participant,

        RegistrationItemType $type,

        string $label,

        string $amount,

        RegistrationItemStatus $status,

        User $actor,

        array $foreignKeys,

    ): RegistrationItem {

        return DB::transaction(function () use ($participant, $type, $label, $amount, $status, $actor, $foreignKeys) {
            $isBillable = (bool) ($foreignKeys['is_billable'] ?? true);
            $adminOverride = (bool) ($foreignKeys['admin_override'] ?? false);
            unset($foreignKeys['is_billable'], $foreignKeys['admin_override']);

            $item = RegistrationItem::query()->create([
                'event_competitor_id' => $participant->id,
                'item_type' => $type,
                'label' => $label,
                'amount' => $amount,
                'status' => $status,
                'is_billable' => $isBillable,
                'admin_override' => $adminOverride,
                ...$foreignKeys,
            ]);



            $billingNote = ($item->is_billable ?? true) ? "{$amount}" : 'sin cobro';



            $this->audit->record(

                actor: $actor,

                eventType: 'registration_item.created',

                severity: AuditSeverity::Info,

                entityType: 'registration_item',

                entityId: $item->id,

                summary: "Inscripción: {$label} — {$billingNote}",

                payloadAfter: [

                    'is_billable' => $item->is_billable,

                    'admin_override' => $item->admin_override,

                ],

            );



            return $item;

        });

    }

}


