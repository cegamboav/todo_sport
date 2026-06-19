<?php

namespace App\Services\Competitive;

use App\Enums\AuditSeverity;
use App\Enums\CategoryGenderScope;
use App\Enums\EventCategoryStatus;
use App\Enums\Gender;
use App\Enums\MatchStatus;
use App\Enums\MatchType;
use App\Enums\ParticipantEnrollmentStatus;
use App\Enums\UserRole;
use App\Models\CategoryCompetitor;
use App\Models\CategoryMatch;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\Ring;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Events\RegistrationCoverageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventCategoryService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RegistrationCoverageService $registrationCoverage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Event $event, array $data, User $actor): EventCategory
    {
        return DB::transaction(function () use ($event, $data, $actor) {
            $this->assertModalityEnabledForEvent($event, (int) $data['modality_id']);

            $category = EventCategory::query()->create([
                'event_id' => $event->id,
                'internal_code' => $this->generateInternalCode($event),
                'name' => $data['name'],
                'modality_id' => $data['modality_id'],
                'gender_scope' => $data['gender_scope'] ?? CategoryGenderScope::Mixed,
                'ring_id' => null,
                'competition_order' => $this->nextCompetitionOrder($event),
                'status' => EventCategoryStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'reference_age' => $data['reference_age'] ?? null,
                'reference_grade' => $data['reference_grade'] ?? null,
                'reference_weight' => $data['reference_weight'] ?? null,
            ]);

            $this->audit->record(
                actor: $actor,
                eventType: 'category.created',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Categoría creada: {$category->name} ({$category->internal_code})",
                payloadAfter: ['event_id' => $event->id],
            );

            return $category->load(['modality:id,code,name', 'ring:id,name']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EventCategory $category, array $data, User $actor): EventCategory
    {
        return DB::transaction(function () use ($category, $data, $actor) {
            $this->assertCategoryMetaEditable($category);

            $event = $category->event;
            $this->assertModalityEnabledForEvent($event, (int) $data['modality_id']);

            $category->update([
                'name' => $data['name'],
                'modality_id' => $data['modality_id'],
                'gender_scope' => $data['gender_scope'] ?? $category->gender_scope,
                'competition_order' => $data['competition_order'] ?? $category->competition_order,
                'notes' => $data['notes'] ?? null,
                'reference_age' => $data['reference_age'] ?? null,
                'reference_grade' => $data['reference_grade'] ?? null,
                'reference_weight' => $data['reference_weight'] ?? null,
            ]);

            $this->audit->record(
                actor: $actor,
                eventType: 'category.updated',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Categoría actualizada: {$category->name}",
            );

            return $category->fresh(['modality:id,code,name', 'ring:id,name']);
        });
    }

    public function updateStatus(EventCategory $category, EventCategoryStatus $status, User $actor, bool $confirmed = false): EventCategory
    {
        return DB::transaction(function () use ($category, $status, $actor, $confirmed) {
            $before = $category->status;
            $this->assertTransition($category, $status, $confirmed);

            if ($before === EventCategoryStatus::BracketPending && $status === EventCategoryStatus::Draft) {
                CategoryMatch::query()
                    ->where('event_category_id', $category->id)
                    ->delete();
            }

            $category->update(['status' => $status]);

            $this->audit->record(
                actor: $actor,
                eventType: 'category.status_changed',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Categoría {$category->internal_code}: {$before->value} → {$status->value}",
            );

            return $category->fresh();
        });
    }

    /**
     * @param  list<array{id: int, competition_order: int}>  $rows
     */
    public function syncCompetitionOrder(Event $event, array $rows, User $actor): void
    {
        DB::transaction(function () use ($event, $rows, $actor) {
            foreach ($rows as $row) {
                EventCategory::query()
                    ->where('event_id', $event->id)
                    ->whereKey($row['id'])
                    ->update(['competition_order' => $row['competition_order']]);
            }

            $this->audit->record(
                actor: $actor,
                eventType: 'category.order_synced',
                severity: AuditSeverity::Info,
                entityType: 'event',
                entityId: $event->id,
                summary: 'Orden de competencia de categorías actualizado',
            );
        });
    }

    public function delete(EventCategory $category, User $actor): void
    {
        DB::transaction(function () use ($category, $actor) {
            $hasCompetitors = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->exists();
            $hasMatches = CategoryMatch::query()
                ->where('event_category_id', $category->id)
                ->exists();

            if ($category->status !== EventCategoryStatus::Draft || $hasCompetitors || $hasMatches) {
                throw ValidationException::withMessages([
                    'category' => 'Solo se puede eliminar una categoría draft, sin competidores y sin combates.',
                ]);
            }

            $name = $category->name;
            $code = $category->internal_code;
            $eventId = $category->event_id;
            $categoryId = $category->id;

            $category->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'category.deleted',
                severity: AuditSeverity::Warning,
                entityType: 'event_category',
                entityId: $categoryId,
                summary: "Categoría eliminada: {$name} ({$code})",
                payloadBefore: ['event_id' => $eventId],
            );
        });
    }

    public function assignCompetitor(
        EventCategory $category,
        EventCompetitor $participant,
        User $actor,
        bool $adminOverride = false,
    ): CategoryCompetitor {
        return DB::transaction(function () use ($category, $participant, $actor, $adminOverride) {
            $this->assertCanEditRoster($category);
            $this->assertParticipantEligible($category, $participant);
            $this->assertParticipantRegisteredForModality($category, $participant);

            $exists = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->where('event_competitor_id', $participant->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'event_competitor_id' => 'Ese competidor ya está en esta categoría.',
                ]);
            }

            $conflict = $this->findModalityCategoryConflict($category, $participant);

            if ($conflict !== null) {
                $modalityName = $category->modality?->name ?? 'esta modalidad';

                if (! $adminOverride || ! $this->isAdmin($actor)) {
                    throw ValidationException::withMessages([
                        'modality_conflict' => "Este participante ya pertenece a otra categoría de la modalidad {$modalityName}.",
                        'existing_category_name' => $conflict->eventCategory->name,
                    ]);
                }
            }

            $nextOrder = (int) CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->max('sort_order') + 1;

            $assignment = CategoryCompetitor::query()->create([
                'event_category_id' => $category->id,
                'event_competitor_id' => $participant->id,
                'sort_order' => $nextOrder,
                'admin_override' => $conflict !== null && $adminOverride && $this->isAdmin($actor),
            ]);

            $participant->load('competitor:id,first_name,last_name');

            $this->audit->record(
                actor: $actor,
                eventType: 'category.competitor_assigned',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Competidor asignado a {$category->internal_code}",
                payloadAfter: [
                    'event_competitor_id' => $participant->id,
                    'admin_override' => $assignment->admin_override,
                    'conflict_category_id' => $conflict?->event_category_id,
                ],
            );

            return $assignment;
        });
    }

    public function removeCompetitor(CategoryCompetitor $assignment, User $actor): void
    {
        DB::transaction(function () use ($assignment, $actor) {
            $category = $assignment->eventCategory;
            $this->assertCanEditRoster($category);

            $categoryId = $category->id;
            $participantId = $assignment->event_competitor_id;
            $assignmentId = $assignment->id;

            $assignment->delete();

            CategoryMatch::query()
                ->where('event_category_id', $categoryId)
                ->where(function ($query) use ($participantId) {
                    $query->where('red_event_competitor_id', $participantId)
                        ->orWhere('blue_event_competitor_id', $participantId)
                        ->orWhere('winner_id', $participantId);
                })
                ->get()
                ->each(function (CategoryMatch $match) use ($participantId) {
                    $updates = [];
                    if ((int) $match->red_event_competitor_id === $participantId) {
                        $updates['red_event_competitor_id'] = null;
                    }
                    if ((int) $match->blue_event_competitor_id === $participantId) {
                        $updates['blue_event_competitor_id'] = null;
                    }
                    if ((int) $match->winner_id === $participantId) {
                        $updates['winner_id'] = null;
                    }
                    if ($updates !== []) {
                        $match->update($updates);
                    }
                });

            $this->audit->record(
                actor: $actor,
                eventType: 'category.competitor_removed',
                severity: AuditSeverity::Warning,
                entityType: 'event_category',
                entityId: $categoryId,
                summary: 'Competidor removido de categoría',
                payloadBefore: ['category_competitor_id' => $assignmentId, 'event_competitor_id' => $participantId],
            );
        });
    }

    public function addMatch(
        EventCategory $category,
        ?int $redParticipantId,
        ?int $blueParticipantId,
        string $stageLabel,
        User $actor,
    ): CategoryMatch {
        return DB::transaction(function () use ($category, $redParticipantId, $blueParticipantId, $stageLabel, $actor) {
            if ($category->status !== EventCategoryStatus::BracketPending) {
                throw ValidationException::withMessages([
                    'category' => 'Solo puedes armar combates cuando la categoría está en bracket_pending.',
                ]);
            }

            $allowedIds = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->pluck('event_competitor_id');

            if ($redParticipantId !== null && ! $allowedIds->contains($redParticipantId)) {
                throw ValidationException::withMessages([
                    'red_event_competitor_id' => 'Competidor rojo no pertenece a esta categoría.',
                ]);
            }

            if ($blueParticipantId !== null && ! $allowedIds->contains($blueParticipantId)) {
                throw ValidationException::withMessages([
                    'blue_event_competitor_id' => 'Competidor azul no pertenece a esta categoría.',
                ]);
            }

            foreach (array_filter([$redParticipantId, $blueParticipantId]) as $pid) {
                $participant = EventCompetitor::query()->with('competitor:id,gender')->find($pid);
                if ($participant !== null) {
                    $this->assertParticipantEligible($category, $participant);
                }
            }

            if ($redParticipantId === null && $blueParticipantId === null) {
                throw ValidationException::withMessages([
                    'red_event_competitor_id' => 'Al menos un lado del combate debe tener competidor.',
                ]);
            }

            $nextOrder = (int) CategoryMatch::query()
                ->where('event_category_id', $category->id)
                ->max('bout_order') + 1;

            $match = CategoryMatch::query()->create([
                'event_id' => $category->event_id,
                'event_category_id' => $category->id,
                'match_code' => app(BracketService::class)->matchCodeFor($category, $nextOrder),
                'red_event_competitor_id' => $redParticipantId,
                'blue_event_competitor_id' => $blueParticipantId,
                'bout_order' => $nextOrder,
                'stage_label' => $stageLabel !== '' ? $stageLabel : 'R1',
                'round_number' => 1,
                'match_type' => ($redParticipantId !== null && $blueParticipantId !== null)
                    ? MatchType::Normal
                    : MatchType::Bye,
                'status' => MatchStatus::Pending,
            ]);

            $this->audit->record(
                actor: $actor,
                eventType: 'category.match_added',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Combate agregado en {$category->internal_code}",
                payloadAfter: ['category_match_id' => $match->id],
            );

            return $match;
        });
    }

    public function removeMatch(CategoryMatch $match, User $actor): void
    {
        DB::transaction(function () use ($match, $actor) {
            $category = $match->category;
            if ($category->status !== EventCategoryStatus::BracketPending) {
                throw ValidationException::withMessages([
                    'category' => 'Solo puedes editar combates en bracket_pending.',
                ]);
            }

            $matchId = $match->id;
            $categoryId = $category->id;
            $match->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'category.match_removed',
                severity: AuditSeverity::Warning,
                entityType: 'event_category',
                entityId: $categoryId,
                summary: 'Combate removido del armado de llave',
                payloadBefore: ['category_match_id' => $matchId],
            );
        });
    }

    /**
     * @param  list<array{
     *   id: int,
     *   red_event_competitor_id?: int|null,
     *   blue_event_competitor_id?: int|null
     * }>  $rows
     */
    public function syncMatches(EventCategory $category, array $rows, User $actor): void
    {
        DB::transaction(function () use ($category, $rows, $actor) {
            if ($category->status !== EventCategoryStatus::BracketPending) {
                throw ValidationException::withMessages([
                    'category' => 'Solo puedes editar llave en bracket_pending.',
                ]);
            }

            $existing = CategoryMatch::query()
                ->where('event_category_id', $category->id)
                ->get()
                ->keyBy('id');

            if ($existing->isEmpty()) {
                throw ValidationException::withMessages([
                    'rows' => 'Genera la estructura de llave antes de asignar participantes.',
                ]);
            }

            $allowedIds = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->pluck('event_competitor_id');

            $usedInInitialRound = collect();

            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if (! $existing->has($id)) {
                    throw ValidationException::withMessages([
                        'rows' => 'Hay encuentros inválidos en la solicitud.',
                    ]);
                }

                /** @var CategoryMatch $match */
                $match = $existing[$id];
                $red = array_key_exists('red_event_competitor_id', $row)
                    ? ($row['red_event_competitor_id'] !== null ? (int) $row['red_event_competitor_id'] : null)
                    : $match->red_event_competitor_id;
                $blue = array_key_exists('blue_event_competitor_id', $row)
                    ? ($row['blue_event_competitor_id'] !== null ? (int) $row['blue_event_competitor_id'] : null)
                    : $match->blue_event_competitor_id;

                foreach ([$red, $blue] as $pid) {
                    if ($pid === null) {
                        continue;
                    }
                    if (! $allowedIds->contains($pid)) {
                        throw ValidationException::withMessages([
                            'rows' => 'Hay competidores fuera de la categoría en la llave.',
                        ]);
                    }
                    $participant = EventCompetitor::query()->with('competitor:id,gender')->find($pid);
                    if ($participant !== null) {
                        $this->assertParticipantEligible($category, $participant);
                    }
                }

                if ($match->round_number === 1 && $match->match_type !== MatchType::Final) {
                    foreach ([$red, $blue] as $pid) {
                        if ($pid === null) {
                            continue;
                        }
                        if ($usedInInitialRound->contains($pid)) {
                            throw ValidationException::withMessages([
                                'rows' => 'Un competidor no puede aparecer dos veces en la primera ronda.',
                            ]);
                        }
                        $usedInInitialRound->push($pid);
                    }
                }

                $matchType = $match->match_type;
                if (in_array($matchType, [MatchType::Normal, MatchType::Bye], true)) {
                    $matchType = ($red !== null && $blue !== null)
                        ? MatchType::Normal
                        : (($red !== null || $blue !== null) ? MatchType::Bye : MatchType::Normal);
                }

                $match->update([
                    'red_event_competitor_id' => $red,
                    'blue_event_competitor_id' => $blue,
                    'match_type' => $matchType,
                    'winner_id' => $matchType === MatchType::Bye
                        ? ($red ?? $blue)
                        : null,
                ]);
            }

            app(BracketService::class)->propagateByeWinnersForCategory($category);

            $this->audit->record(
                actor: $actor,
                eventType: 'category.matches_synced',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Llave actualizada ({$existing->count()} encuentros)",
            );
        });
    }

    private function generateInternalCode(Event $event): string
    {
        $maxNumeric = EventCategory::query()
            ->where('event_id', $event->id)
            ->pluck('internal_code')
            ->map(function (string $code) {
                if (preg_match('/CAT-(\d+)/', $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        $next = ($maxNumeric ?? 0) + 1;

        return sprintf('CAT-%06d', $next);
    }

    private function nextCompetitionOrder(Event $event): int
    {
        $max = EventCategory::query()
            ->where('event_id', $event->id)
            ->max('competition_order');

        return ((int) $max) + 10;
    }

    private function assertModalityEnabledForEvent(Event $event, int $modalityId): void
    {
        $enabled = EventModality::query()
            ->where('event_id', $event->id)
            ->where('modality_id', $modalityId)
            ->where('enabled', true)
            ->exists();

        if (! $enabled) {
            throw ValidationException::withMessages([
                'modality_id' => 'La modalidad no está habilitada en este evento.',
            ]);
        }
    }

    private function assertRingBelongsToEvent(Event $event, mixed $ringId): void
    {
        if ($ringId === null || $ringId === '') {
            return;
        }

        $valid = Ring::query()
            ->where('event_id', $event->id)
            ->whereKey((int) $ringId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'ring_id' => 'Ring inválido para este evento.',
            ]);
        }
    }

    private function assertCategoryMetaEditable(EventCategory $category): void
    {
        if (in_array($category->status, [
            EventCategoryStatus::InProgress,
            EventCategoryStatus::Finished,
            EventCategoryStatus::Awarded,
        ], true)) {
            throw ValidationException::withMessages([
                'category' => 'Esta categoría ya está bloqueada para edición de datos.',
            ]);
        }
    }

    private function assertCanEditRoster(EventCategory $category): void
    {
        if ($category->status !== EventCategoryStatus::Draft) {
            throw ValidationException::withMessages([
                'category' => 'Los competidores solo se editan en estado draft.',
            ]);
        }
    }

    private function assertTransition(EventCategory $category, EventCategoryStatus $next, bool $confirmed = false): void
    {
        $current = $category->status;

        if ($current === $next) {
            return;
        }

        if ($current === EventCategoryStatus::BracketPending && $next === EventCategoryStatus::Draft) {
            if (! $confirmed) {
                throw ValidationException::withMessages([
                    'confirmation' => 'Esta acción eliminará todos los encuentros y la estructura de la llave. ¿Desea continuar?',
                ]);
            }

            return;
        }

        if ($current === EventCategoryStatus::Ready && $next === EventCategoryStatus::BracketPending) {
            return;
        }

        if ($current === EventCategoryStatus::BracketPending && $next === EventCategoryStatus::Ready) {
            if (! $this->hasValidBracketForReady($category)) {
                throw ValidationException::withMessages([
                    'status' => 'No puedes mover a lista: los encuentros iniciales deben tener dos competidores o un competidor con bye.',
                ]);
            }

            return;
        }

        if ($current === EventCategoryStatus::Assigned && $next === EventCategoryStatus::Ready) {
            return;
        }

        if ($current === EventCategoryStatus::Ready && $next === EventCategoryStatus::Assigned) {
            if ($category->ring_id === null) {
                throw ValidationException::withMessages([
                    'status' => 'Asigna un ring antes de pasar a assigned.',
                ]);
            }

            return;
        }

        if ($current === EventCategoryStatus::Assigned && $next === EventCategoryStatus::InProgress) {
            return;
        }

        if ($current === EventCategoryStatus::InProgress && $next === EventCategoryStatus::Finished) {
            return;
        }

        if ($current === EventCategoryStatus::Finished && $next === EventCategoryStatus::Awarded) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "Transición inválida: {$current->value} → {$next->value}.",
        ]);
    }

    private function hasValidBracketForReady(EventCategory $category): bool
    {
        $matches = CategoryMatch::query()
            ->where('event_category_id', $category->id)
            ->orderBy('round_number')
            ->orderBy('bout_order')
            ->get();

        if ($matches->isEmpty()) {
            return false;
        }

        if ($matches->count() === 1 && $matches->first()->match_type === MatchType::Final) {
            $match = $matches->first();

            return $match->hasBothCompetitors();
        }

        $initialMatches = $matches->filter(function (CategoryMatch $match) {
            if ($match->match_type === MatchType::Final) {
                return false;
            }
            if ($match->match_type === MatchType::ThirdPlace) {
                return false;
            }

            return $match->round_number === 1;
        });

        if ($initialMatches->isEmpty()) {
            return false;
        }

        foreach ($initialMatches as $match) {
            if (! $this->isInitialMatchReady($match)) {
                return false;
            }
        }

        return true;
    }

    private function isInitialMatchReady(CategoryMatch $match): bool
    {
        if ($match->hasBothCompetitors()) {
            return true;
        }

        if ($match->hasExactlyOneCompetitor()) {
            return true;
        }

        if (
            $match->match_type === MatchType::Bye
            && $match->red_event_competitor_id === null
            && $match->blue_event_competitor_id === null
        ) {
            return true;
        }

        return false;
    }

    private function assertParticipantEligible(EventCategory $category, EventCompetitor $participant): void
    {
        if ($participant->event_id !== $category->event_id) {
            throw ValidationException::withMessages([
                'event_competitor_id' => 'Participante inválido para este evento.',
            ]);
        }

        if ($participant->status !== ParticipantEnrollmentStatus::Active) {
            throw ValidationException::withMessages([
                'event_competitor_id' => 'Solo participantes activos pueden asignarse a categorías.',
            ]);
        }

        $participant->loadMissing('competitor:id,gender');
        $this->assertCompetitorMatchesGenderScope($category, $participant->competitor?->gender);
    }

    private function assertCompetitorMatchesGenderScope(EventCategory $category, ?Gender $gender): void
    {
        if ($gender === null) {
            return;
        }

        $scope = $category->gender_scope;

        $allowed = match ($scope) {
            CategoryGenderScope::Male => [Gender::Male],
            CategoryGenderScope::Female => [Gender::Female],
            CategoryGenderScope::Mixed => [Gender::Male, Gender::Female],
        };

        if (! in_array($gender, $allowed, true)) {
            throw ValidationException::withMessages([
                'event_competitor_id' => 'El sexo del competidor no coincide con el sexo de la categoría.',
            ]);
        }
    }

    /**
     * @param  Builder<EventCompetitor>  $query
     */
    public static function applyGenderScopeToCompetitorsQuery($query, CategoryGenderScope $scope): void
    {
        match ($scope) {
            CategoryGenderScope::Male => $query->whereHas('competitor', fn ($q) => $q->where('gender', Gender::Male)),
            CategoryGenderScope::Female => $query->whereHas('competitor', fn ($q) => $q->where('gender', Gender::Female)),
            CategoryGenderScope::Mixed => $query->whereHas('competitor', fn ($q) => $q->whereIn('gender', [Gender::Male->value, Gender::Female->value])),
        };
    }

    public static function eventModalityIdForCategory(EventCategory $category): ?int
    {
        return EventModality::query()
            ->where('event_id', $category->event_id)
            ->where('modality_id', $category->modality_id)
            ->value('id');
    }

    private function assertParticipantRegisteredForModality(EventCategory $category, EventCompetitor $participant): void
    {
        $eventModalityId = self::eventModalityIdForCategory($category);

        if ($eventModalityId === null) {
            throw ValidationException::withMessages([
                'event_competitor_id' => 'La modalidad de esta categoría no está habilitada en el evento.',
            ]);
        }

        if (! $this->registrationCoverage->isRegisteredForEventModality($participant, $eventModalityId)) {
            throw ValidationException::withMessages([
                'event_competitor_id' => 'El participante no está inscrito en la modalidad requerida para esta categoría.',
            ]);
        }
    }

    private function findModalityCategoryConflict(EventCategory $category, EventCompetitor $participant): ?CategoryCompetitor
    {
        return CategoryCompetitor::query()
            ->where('event_competitor_id', $participant->id)
            ->whereHas('eventCategory', fn (Builder $q) => $q
                ->where('event_id', $category->event_id)
                ->where('modality_id', $category->modality_id)
                ->whereKeyNot($category->id))
            ->with('eventCategory:id,name,modality_id')
            ->first();
    }

    private function isAdmin(User $actor): bool
    {
        return $actor->role === UserRole::Admin;
    }
}
