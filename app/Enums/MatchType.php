<?php

namespace App\Enums;

enum MatchType: string
{
    case Normal = 'normal';
    case Bye = 'bye';
    case ThirdPlace = 'third_place';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Bye => 'Bye',
            self::ThirdPlace => 'Tercer lugar',
            self::Final => 'Final',
        };
    }
}
