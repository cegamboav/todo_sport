<?php

namespace Tests\Unit;

use App\Enums\ParticipantEnrollmentStatus;
use App\Models\CategoryCompetitor;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\RegistrationItem;
use App\Services\Competitive\EventCategoryService;
use App\Services\Competitive\ParticipantCategoryAssignmentService;
use App\Services\Events\RegistrationCoverageService;
use Tests\TestCase;

class ParticipantCategoryAssignmentServiceTest extends TestCase
{
    private ParticipantCategoryAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ParticipantCategoryAssignmentService::class);
    }

    public function test_combo_participant_with_partial_assignment_shows_pending_modalities(): void
    {
        $formasCategory = EventCategory::query()
            ->where('modality_id', 2)
            ->where('status', 'draft')
            ->first();

        if ($formasCategory === null) {
            $this->markTestSkipped('Requires seeded Formas category.');
        }

        $event = Event::query()->findOrFail($formasCategory->event_id);
        $coverage = app(RegistrationCoverageService::class);

        $comboParticipant = EventCompetitor::query()
            ->where('event_id', $event->id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->whereHas('registrationItems', fn ($q) => $q->whereNotNull('event_combo_id'))
            ->first();

        if ($comboParticipant === null) {
            $this->markTestSkipped('Requires combo registration participant.');
        }

        $enrolledModalityIds = EventModality::query()
            ->where('event_id', $event->id)
            ->where('enabled', true)
            ->get()
            ->filter(fn (EventModality $row) => $coverage->isRegisteredForEventModality($comboParticipant, $row->id))
            ->pluck('modality_id')
            ->unique()
            ->values()
            ->all();

        if (count($enrolledModalityIds) < 2) {
            $this->markTestSkipped('Requires participant enrolled in at least two modalities.');
        }

        $existingAssignments = CategoryCompetitor::query()
            ->where('event_competitor_id', $comboParticipant->id)
            ->get();

        CategoryCompetitor::query()
            ->where('event_competitor_id', $comboParticipant->id)
            ->delete();

        $assignedCategory = EventCategory::query()
            ->where('event_id', $event->id)
            ->where('modality_id', $enrolledModalityIds[0])
            ->first();

        if ($assignedCategory === null) {
            $this->markTestSkipped('Requires category for first enrolled modality.');
        }

        CategoryCompetitor::query()->create([
            'event_category_id' => $assignedCategory->id,
            'event_competitor_id' => $comboParticipant->id,
            'sort_order' => 0,
        ]);

        try {
            $report = $this->service->pendingCategorizationReport($event);

            $row = collect($report['participants'])
                ->firstWhere('event_competitor_id', $comboParticipant->id);

            $this->assertNotNull($row);
            $this->assertContains(
                $enrolledModalityIds[0],
                collect($row['enrolled_modalities'])->pluck('modality_id')->all(),
            );
            $this->assertContains(
                $enrolledModalityIds[0],
                collect($row['assigned_modalities'])->pluck('modality_id')->all(),
            );
            $this->assertContains(
                $enrolledModalityIds[1],
                collect($row['pending_modalities'])->pluck('modality_id')->all(),
            );

            $summaryModalityIds = collect($report['summary_by_modality'])->pluck('modality_id')->all();
            $this->assertContains($enrolledModalityIds[1], $summaryModalityIds);
        } finally {
            CategoryCompetitor::query()
                ->where('event_competitor_id', $comboParticipant->id)
                ->delete();

            foreach ($existingAssignments as $assignment) {
                CategoryCompetitor::query()->create($assignment->only([
                    'event_category_id',
                    'event_competitor_id',
                    'sort_order',
                    'admin_override',
                ]));
            }
        }
    }

    public function test_fully_assigned_participant_is_excluded_from_report(): void
    {
        $formasCategory = EventCategory::query()
            ->where('modality_id', 2)
            ->first();

        if ($formasCategory === null) {
            $this->markTestSkipped('Requires seeded Formas category.');
        }

        $eventModalityId = EventCategoryService::eventModalityIdForCategory($formasCategory);
        $directItem = RegistrationItem::query()
            ->where('event_modality_id', $eventModalityId)
            ->first();

        if ($directItem === null) {
            $this->markTestSkipped('Requires direct Formas registration item.');
        }

        $participant = $directItem->eventCompetitor;
        $event = Event::query()->findOrFail($participant->event_id);

        $existingAssignments = CategoryCompetitor::query()
            ->where('event_competitor_id', $participant->id)
            ->get();

        CategoryCompetitor::query()
            ->where('event_competitor_id', $participant->id)
            ->delete();

        CategoryCompetitor::query()->create([
            'event_category_id' => $formasCategory->id,
            'event_competitor_id' => $participant->id,
            'sort_order' => 0,
        ]);

        try {
            $report = $this->service->pendingCategorizationReport($event);

            $this->assertNull(
                collect($report['participants'])->firstWhere('event_competitor_id', $participant->id),
            );
        } finally {
            CategoryCompetitor::query()
                ->where('event_competitor_id', $participant->id)
                ->delete();

            foreach ($existingAssignments as $assignment) {
                CategoryCompetitor::query()->create($assignment->only([
                    'event_category_id',
                    'event_competitor_id',
                    'sort_order',
                    'admin_override',
                ]));
            }
        }
    }

    public function test_unassigned_direct_registration_appears_as_pending(): void
    {
        $formasCategory = EventCategory::query()
            ->where('modality_id', 2)
            ->first();

        if ($formasCategory === null) {
            $this->markTestSkipped('Requires seeded Formas category.');
        }

        $eventModalityId = EventCategoryService::eventModalityIdForCategory($formasCategory);
        $directItem = RegistrationItem::query()
            ->where('event_modality_id', $eventModalityId)
            ->first();

        if ($directItem === null) {
            $this->markTestSkipped('Requires direct Formas registration item.');
        }

        $participant = $directItem->eventCompetitor;
        $event = Event::query()->findOrFail($participant->event_id);

        $existingAssignments = CategoryCompetitor::query()
            ->where('event_competitor_id', $participant->id)
            ->get();

        CategoryCompetitor::query()
            ->where('event_competitor_id', $participant->id)
            ->delete();

        try {
            $report = $this->service->pendingCategorizationReport($event);

            $row = collect($report['participants'])
                ->firstWhere('event_competitor_id', $participant->id);

            $this->assertNotNull($row);
            $this->assertSame([], $row['assigned_modalities']);
            $this->assertSame(
                [2],
                collect($row['pending_modalities'])->pluck('modality_id')->all(),
            );
        } finally {
            CategoryCompetitor::query()
                ->where('event_competitor_id', $participant->id)
                ->delete();

            foreach ($existingAssignments as $assignment) {
                CategoryCompetitor::query()->create($assignment->only([
                    'event_category_id',
                    'event_competitor_id',
                    'sort_order',
                    'admin_override',
                ]));
            }
        }
    }
}
