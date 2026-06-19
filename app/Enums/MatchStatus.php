<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Scheduled => 'Programado',
            self::InProgress => 'En curso',
            self::Finished => 'Finalizado',
        };
    }
}
