<?php

namespace App\Enums;

enum GradeCategory: string
{
    case Kup = 'kup';
    case Dan = 'dan';

    public function label(): string
    {
        return match ($this) {
            self::Kup => 'Kup',
            self::Dan => 'Dan',
        };
    }
}
