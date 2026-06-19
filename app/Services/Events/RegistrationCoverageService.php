<?php

namespace App\Services\Events;

use App\Models\EventCompetitor;
use App\Models\EventModality;
use App\Models\RegistrationItem;
use Illuminate\Database\Eloquent\Builder;

class RegistrationCoverageService
{
    public function isRegisteredForEventModality(EventCompetitor $participant, int $eventModalityId): bool
    {
        $eventModality = EventModality::query()->find($eventModalityId);

        if ($eventModality === null || ! $eventModality->enabled) {
            return false;
        }

        return RegistrationItem::query()
            ->where('event_competitor_id', $participant->id)
            ->where(fn (Builder $query) => self::applyEventModalityCoverageConstraint($query, $eventModality))
            ->exists();
    }

    /**
     * @param  Builder<EventCompetitor>  $query
     */
    public static function scopeRegisteredForEventModality(Builder $query, int $eventModalityId): void
    {
        $eventModality = EventModality::query()->find($eventModalityId);

        if ($eventModality === null || ! $eventModality->enabled) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereHas(
            'registrationItems',
            fn (Builder $items) => self::applyEventModalityCoverageConstraint($items, $eventModality),
        );
    }

    /**
     * @param  Builder<RegistrationItem>  $query
     */
    private static function applyEventModalityCoverageConstraint(Builder $query, EventModality $eventModality): void
    {
        $query->where(function (Builder $coverage) use ($eventModality) {
            $coverage->where('event_modality_id', $eventModality->id)
                ->orWhere(function (Builder $combo) use ($eventModality) {
                    $combo->whereNotNull('event_combo_id')
                        ->whereHas('eventCombo', function (Builder $eventCombo) use ($eventModality) {
                            $eventCombo->where('event_id', $eventModality->event_id)
                                ->where('enabled', true)
                                ->whereHas(
                                    'modalities',
                                    fn (Builder $modalities) => $modalities->whereKey($eventModality->modality_id),
                                );
                        });
                });
        });
    }
}
