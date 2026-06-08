<?php

namespace App\Enums;

enum OperationalSessionType: string
{
    case CategoryEdit = 'category_edit';
    case BracketRegenerate = 'bracket_regenerate';
    case CheckinBatch = 'checkin_batch';
    case PodiumEdit = 'podium_edit';
    case RingAssignCategories = 'ring_assign_categories';

    public function defaultLockStrength(): OperationalLockStrength
    {
        return match ($this) {
            self::BracketRegenerate => OperationalLockStrength::Strong,
            default => OperationalLockStrength::Soft,
        };
    }
}
