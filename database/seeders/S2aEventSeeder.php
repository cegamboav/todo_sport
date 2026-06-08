<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\RegistrationItemStatus;
use App\Enums\RegistrationItemType;
use App\Enums\ThirdPlaceMode;
use App\Models\Competitor;
use App\Models\Event;
use App\Models\EventCombo;
use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\EventSetting;
use App\Models\Modality;
use App\Models\RegistrationItem;
use App\Models\School;
use App\Services\Auth\AdminAccessService;
use App\Models\User;
use Illuminate\Database\Seeder;

class S2aEventSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->where('abbreviation', 'DEMO')->first();
        $admin = User::query()->where('username', 'admin')->first();

        $event = Event::query()->updateOrCreate(
            ['name' => 'Torneo Demo S1'],
            [
                'status' => EventStatus::Operational,
                'event_date' => now()->addMonths(2)->toDateString(),
                'venue' => 'Polideportivo Demo',
                'host_school_id' => $school?->id,
                'notes' => 'Evento demo S2A — configuración e inscripciones',
            ],
        );

        EventSetting::query()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'third_place_mode' => ThirdPlaceMode::DualBronze,
                'allow_team_forms' => true,
                'bronze_mode' => 'repechage',
            ],
        );

        foreach (Modality::query()->where('is_active', true)->get() as $modality) {
            EventModality::query()->updateOrCreate(
                ['event_id' => $event->id, 'modality_id' => $modality->id],
                [
                    'enabled' => in_array($modality->code, ['sparring', 'patterns'], true),
                    'price' => 100,
                ],
            );
        }

        $sparring = Modality::query()->where('code', 'sparring')->first();
        $patterns = Modality::query()->where('code', 'patterns')->first();

        if ($sparring && $patterns) {
            $combo = EventCombo::query()->updateOrCreate(
                ['event_id' => $event->id, 'name' => 'Combate + Formas'],
                ['price' => 180, 'enabled' => true, 'sort_order' => 1],
            );
            $combo->modalities()->sync([$sparring->id, $patterns->id]);
        }

        $competitor = Competitor::query()->where('first_name', 'Ana')->first();
        if ($competitor !== null) {
            $participant = EventCompetitor::query()->firstOrCreate(
                ['event_id' => $event->id, 'competitor_id' => $competitor->id],
            );

            $eventModality = EventModality::query()
                ->where('event_id', $event->id)
                ->whereHas('modality', fn ($q) => $q->where('code', 'sparring'))
                ->first();

            if ($eventModality !== null) {
                RegistrationItem::query()->firstOrCreate(
                    [
                        'event_competitor_id' => $participant->id,
                        'event_modality_id' => $eventModality->id,
                        'item_type' => RegistrationItemType::Modality,
                    ],
                    [
                        'label' => 'Combate',
                        'amount' => 100,
                        'status' => RegistrationItemStatus::Pending,
                    ],
                );
            }
        }

        if ($admin !== null) {
            app(AdminAccessService::class)->assignUserToEvent(
                $event,
                User::query()->where('username', 'laura')->first() ?? $admin,
                $admin,
            );
        }
    }
}
