<?php

namespace Tests\Unit;

use App\Enums\ParticipantEnrollmentStatus;
use App\Models\EventCategory;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\RegistrationItem;
use App\Services\Competitive\EventCategoryService;
use App\Services\Events\RegistrationCoverageService;
use Tests\TestCase;

class RegistrationCoverageServiceTest extends TestCase
{
    private RegistrationCoverageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RegistrationCoverageService::class);
    }

    public function test_combo_registration_covers_included_modalities(): void
    {
        $formasCategory = EventCategory::query()
            ->where('modality_id', 2)
            ->where('status', 'draft')
            ->first();

        if ($formasCategory === null) {
            $this->markTestSkipped('Requires seeded Formas category.');
        }

        $eventModalityId = EventCategoryService::eventModalityIdForCategory($formasCategory);
        $this->assertNotNull($eventModalityId);

        $comboParticipant = EventCompetitor::query()
            ->where('event_id', $formasCategory->event_id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->whereHas('registrationItems', fn ($q) => $q->whereNotNull('event_combo_id'))
            ->first();

        $this->assertNotNull($comboParticipant);
        $this->assertTrue(
            $this->service->isRegisteredForEventModality($comboParticipant, $eventModalityId),
        );
    }

    public function test_direct_modality_registration_still_counts(): void
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
        $this->assertTrue(
            $this->service->isRegisteredForEventModality($participant, $eventModalityId),
        );
    }

    public function test_disabled_combo_does_not_grant_modality_coverage(): void
    {
        $formasEventModality = EventModality::query()
            ->whereHas('modality', fn ($q) => $q->where('code', 'patterns'))
            ->first();

        if ($formasEventModality === null) {
            $this->markTestSkipped('Requires seeded Formas event modality.');
        }

        $comboItem = RegistrationItem::query()
            ->whereNotNull('event_combo_id')
            ->with('eventCombo')
            ->first();

        if ($comboItem === null || $comboItem->eventCombo === null) {
            $this->markTestSkipped('Requires combo registration item.');
        }

        $comboItem->eventCombo->update(['enabled' => false]);

        try {
            $this->assertFalse(
                $this->service->isRegisteredForEventModality(
                    $comboItem->eventCompetitor,
                    $formasEventModality->id,
                ),
            );
        } finally {
            $comboItem->eventCombo->update(['enabled' => true]);
        }
    }

    public function test_scope_includes_more_participants_than_direct_modality_items_only(): void
    {
        $formasCategory = EventCategory::query()
            ->where('modality_id', 2)
            ->where('status', 'draft')
            ->first();

        if ($formasCategory === null) {
            $this->markTestSkipped('Requires seeded Formas category.');
        }

        $eventModalityId = EventCategoryService::eventModalityIdForCategory($formasCategory);

        $directOnly = EventCompetitor::query()
            ->where('event_id', $formasCategory->event_id)
            ->where('status', ParticipantEnrollmentStatus::Active)
            ->whereHas('registrationItems', fn ($q) => $q->where('event_modality_id', $eventModalityId))
            ->count();

        $scoped = EventCompetitor::query()
            ->where('event_id', $formasCategory->event_id)
            ->where('status', ParticipantEnrollmentStatus::Active);

        RegistrationCoverageService::scopeRegisteredForEventModality($scoped, $eventModalityId);

        $this->assertGreaterThan($directOnly, $scoped->count());
    }
}
