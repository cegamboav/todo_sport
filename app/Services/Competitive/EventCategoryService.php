<?php

namespace App\Services\Competitive;

use App\Enums\CategoryGenderScope;
use App\Enums\EventCategoryStatus;
use App\Enums\Gender;
use App\Enums\ParticipantEnrollmentStatus;
use App\Models\CategoryCompetitor;
use App\Models\CategoryMatch;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\Ring;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Enums\AuditSeverity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventCategoryService
{
    public function __construct(
        private readonly AuditService $audit,
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
            $this->assertRingBelongsToEvent($event, $data['ring_id'] ?? null);

            $category->update([
                'name' => $data['name'],
                'modality_id' => $data['modality_id'],
                'gender_scope' => $data['gender_scope'] ?? $category->gender_scope,
                'ring_id' => $data['ring_id'] ?? null,
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

    public function updateStatus(EventCategory $category, EventCategoryStatus $status, User $actor): EventCategory
    {
        return DB::transaction(function () use ($category, $status, $actor) {
            $before = $category->status;
            $this->assertTransition($category, $status);
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

    public function assignCompetitor(EventCategory $category, EventCompetitor $participant, User $actor): CategoryCompetitor
    {
        return DB::transaction(function () use ($category, $participant, $actor) {
            $this->assertCanEditRoster($category);
            $this->assertParticipantEligible($category, $participant);

            $exists = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->where('event_competitor_id', $participant->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'event_competitor_id' => 'Ese competidor ya está en esta categoría.',
                ]);
            }

            $nextOrder = (int) CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->max('sort_order') + 1;

            $assignment = CategoryCompetitor::query()->create([
                'event_category_id' => $category->id,
                'event_competitor_id' => $participant->id,
                'sort_order' => $nextOrder,
            ]);

            $participant->load('competitor:id,first_name,last_name');

            $this->audit->record(
                actor: $actor,
                eventType: 'category.competitor_assigned',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Competidor asignado a {$category->internal_code}",
                payloadAfter: ['event_competitor_id' => $participant->id],
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
                        ->orWhere('blue_event_competitor_id', $participantId);
                })
                ->delete();

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
                'event_category_id' => $category->id,
                'red_event_competitor_id' => $redParticipantId,
                'blue_event_competitor_id' => $blueParticipantId,
                'bout_order' => $nextOrder,
                'stage_label' => $stageLabel !== '' ? $stageLabel : 'R1',
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
     *   id?: int|null,
     *   bout_order: int,
     *   stage_label?: string|null,
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

            $allowedIds = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->pluck('event_competitor_id');

            $used = collect();
            foreach ($rows as $row) {
                $red = isset($row['red_event_competitor_id']) ? (int) $row['red_event_competitor_id'] : null;
                $blue = isset($row['blue_event_competitor_id']) ? (int) $row['blue_event_competitor_id'] : null;

                if ($red === null && $blue === null) {
                    continue;
                }

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
                    if ($used->contains($pid)) {
                        throw ValidationException::withMessages([
                            'rows' => 'Un competidor no puede aparecer dos veces en la llave.',
                        ]);
                    }
                    $used->push($pid);
                }
            }

            $current = CategoryMatch::query()
                ->where('event_category_id', $category->id)
                ->get()
                ->keyBy('id');

            $keepIds = collect();
            foreach ($rows as $idx => $row) {
                $red = isset($row['red_event_competitor_id']) ? (int) $row['red_event_competitor_id'] : null;
                $blue = isset($row['blue_event_competitor_id']) ? (int) $row['blue_event_competitor_id'] : null;
                if ($red === null && $blue === null) {
                    continue;
                }

                $payload = [
                    'event_category_id' => $category->id,
                    'red_event_competitor_id' => $red,
                    'blue_event_competitor_id' => $blue,
                    'bout_order' => (int) ($row['bout_order'] ?? ($idx + 1)),
                    'stage_label' => (string) ($row['stage_label'] ?? 'R1'),
                ];

                $id = isset($row['id']) ? (int) $row['id'] : null;
                if ($id !== null && $current->has($id)) {
                    $current[$id]->update($payload);
                    $keepIds->push($id);
                    continue;
                }

                $match = CategoryMatch::query()->create($payload);
                $keepIds->push($match->id);
            }

            CategoryMatch::query()
                ->where('event_category_id', $category->id)
                ->when($keepIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();

            $this->audit->record(
                actor: $actor,
                eventType: 'category.matches_synced',
                severity: AuditSeverity::Info,
                entityType: 'event_category',
                entityId: $category->id,
                summary: "Llave manual sincronizada ({$keepIds->count()} combates)",
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

    private function assertTransition(EventCategory $category, EventCategoryStatus $next): void
    {
        $current = $category->status;

        if ($current === $next) {
            return;
        }

        if ($current === EventCategoryStatus::Draft && $next === EventCategoryStatus::BracketPending) {
            $competitorCount = CategoryCompetitor::query()
                ->where('event_category_id', $category->id)
                ->count();
            if ($competitorCount < 2) {
                throw ValidationException::withMessages([
                    'status' => 'Necesitas al menos 2 competidores para poder pasar a armar las llaves.',
                ]);
            }

            return;
        }

        if ($current === EventCategoryStatus::BracketPending && $next === EventCategoryStatus::Draft) {
            return;
        }

        if ($current === EventCategoryStatus::BracketPending && $next === EventCategoryStatus::Ready) {
            if (! $this->hasValidManualBracket($category)) {
                throw ValidationException::withMessages([
                    'status' => 'No puedes pasar a ready sin una llave manual válida.',
                ]);
            }

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

    private function hasValidManualBracket(EventCategory $category): bool
    {
        $assignedCount = CategoryCompetitor::query()
            ->where('event_category_id', $category->id)
            ->count();

        if ($assignedCount < 2) {
            return false;
        }

        $matches = CategoryMatch::query()
            ->where('event_category_id', $category->id)
            ->get(['red_event_competitor_id', 'blue_event_competitor_id']);

        if ($matches->isEmpty()) {
            return false;
        }

        $covered = collect();
        foreach ($matches as $match) {
            if ($match->red_event_competitor_id !== null) {
                $covered->push((int) $match->red_event_competitor_id);
            }
            if ($match->blue_event_competitor_id !== null) {
                $covered->push((int) $match->blue_event_competitor_id);
            }
        }

        $covered = $covered->unique();
        $assignedIds = CategoryCompetitor::query()
            ->where('event_category_id', $category->id)
            ->pluck('event_competitor_id');

        return $assignedIds->diff($covered)->isEmpty();
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
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\EventCompetitor>  $query
     */
    public static function applyGenderScopeToCompetitorsQuery($query, CategoryGenderScope $scope): void
    {
        match ($scope) {
            CategoryGenderScope::Male => $query->whereHas('competitor', fn ($q) => $q->where('gender', Gender::Male)),
            CategoryGenderScope::Female => $query->whereHas('competitor', fn ($q) => $q->where('gender', Gender::Female)),
            CategoryGenderScope::Mixed => $query->whereHas('competitor', fn ($q) => $q->whereIn('gender', [Gender::Male->value, Gender::Female->value])),
        };
    }
}
