<?php

namespace App\Enums;

enum ParticipantEnrollmentStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Withdrawn => 'Desinscrito',
        };
    }
}
