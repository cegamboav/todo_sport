<?php

namespace App\Services\Competitive;

use App\Enums\ParticipantEnrollmentStatus;
use App\Models\Event;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Services\Events\RegistrationCoverageService;
use Illuminate\Support\Collection;

class ParticipantCategoryAssignmentService
{
    public function __construct(
        private readonly RegistrationCoverageService $registrationCoverage,
    ) {}

    /**
     * @return array{
     *   total_with_pending: int,
     *   summary_by_modality: list<array{modality_id: int, name: string, count: int}>,
     *   participants: list<array{
     *     event_competitor_id: int,
     *     competitor: array{first_name: string, last_name: string, school?: string|null},
     *     enrolled_modalities: list<array{modality_id: int, name: string}>,
     *     assigned_modalities: list<array{modality_id: int, name: string, category_name: string}>,
     *     pending_modalities: list<array{modality_id: int, name: string}>
     *   }>
     * }
     */
    public function pendingCategorizationReport(Event $event): array
    {
        $eventModalities = EventModality::query()
            ->where('event_id', $event->id)
            ->where('enabled', true)
            ->with('modality:id,code,name,sort_order')
            ->get()
            ->sortBy(fn (EventModality $row) => $row->modality->sort_order ?? 0)
            ->values();

        $participants = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->with([
                'competitor:id,first_name,last_name,school_id',
                'competitor.school:id,name,abbreviation',
                'categoryCompetitors.eventCategory:id,name,modality_id',
                'categoryCompetitors.eventCategory.modality:id,name',
            ])
            ->get()
            ->sortBy(fn (EventCompetitor $participant) => strtolower(
                ($participant->competitor->last_name ?? '').' '.($participant->competitor->first_name ?? ''),
            ))
            ->values();

        /** @var array<int, int> $summaryCounts */
        $summaryCounts = $eventModalities
            ->mapWithKeys(fn (EventModality $row) => [$row->modality_id => 0])
            ->all();

        $rows = [];

        foreach ($participants as $participant) {
            $enrolled = $this->enrolledModalitiesForParticipant($participant, $eventModalities);

            if ($enrolled->isEmpty()) {
                continue;
            }

            $assigned = $this->assignedModalitiesForParticipant($participant);
            $assignedModalityIds = $assigned->pluck('modality_id')->all();

            $pending = $enrolled
                ->reject(fn (array $modality) => in_array($modality['modality_id'], $assignedModalityIds, true))
                ->values();

            if ($pending->isEmpty()) {
                continue;
            }

            foreach ($pending as $modality) {
                $summaryCounts[$modality['modality_id']] = ($summaryCounts[$modality['modality_id']] ?? 0) + 1;
            }

            $school = $participant->competitor->school;

            $rows[] = [
                'event_competitor_id' => $participant->id,
                'competitor' => [
                    'first_name' => $participant->competitor->first_name,
                    'last_name' => $participant->competitor->last_name,
                    'school' => $school?->abbreviation ?: $school?->name,
                ],
                'enrolled_modalities' => $enrolled->values()->all(),
                'assigned_modalities' => $assigned->values()->all(),
                'pending_modalities' => $pending->all(),
            ];
        }

        $summaryByModality = $eventModalities
            ->map(fn (EventModality $row) => [
                'modality_id' => $row->modality_id,
                'name' => $row->modality->name,
                'count' => $summaryCounts[$row->modality_id] ?? 0,
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();

        return [
            'total_with_pending' => count($rows),
            'summary_by_modality' => $summaryByModality,
            'participants' => $rows,
        ];
    }

    /**
     * @param  Collection<int, EventModality>  $eventModalities
     * @return Collection<int, array{modality_id: int, name: string}>
     */
    private function enrolledModalitiesForParticipant(
        EventCompetitor $participant,
        Collection $eventModalities,
    ): Collection {
        return $eventModalities
            ->filter(fn (EventModality $eventModality) => $this->registrationCoverage
                ->isRegisteredForEventModality($participant, $eventModality->id))
            ->map(fn (EventModality $eventModality) => [
                'modality_id' => $eventModality->modality_id,
                'name' => $eventModality->modality->name,
            ])
            ->unique('modality_id')
            ->values();
    }

    /**
     * @return Collection<int, array{modality_id: int, name: string, category_name: string}>
     */
    private function assignedModalitiesForParticipant(EventCompetitor $participant): Collection
    {
        return $participant->categoryCompetitors
            ->map(function ($assignment) {
                $category = $assignment->eventCategory;
                if ($category === null || $category->modality === null) {
                    return null;
                }

                return [
                    'modality_id' => $category->modality_id,
                    'name' => $category->modality->name,
                    'category_name' => $category->name,
                ];
            })
            ->filter()
            ->unique('modality_id')
            ->values();
    }
}
