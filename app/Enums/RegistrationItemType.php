<?php

namespace App\Enums;

enum RegistrationItemType: string
{
    case Modality = 'modality';
    case Combo = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::Modality => 'Modalidad',
            self::Combo => 'Combo',
        };
    }
}
