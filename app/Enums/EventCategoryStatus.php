<?php

namespace App\Enums;

enum EventCategoryStatus: string
{
    case Draft = 'draft';
    case BracketPending = 'bracket_pending';
    case Ready = 'ready';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Awarded = 'awarded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::BracketPending => 'Llave pendiente',
            self::Ready => 'Llave lista',
            self::Assigned => 'Asignada a ring',
            self::InProgress => 'En competencia',
            self::Finished => 'Resultados listos',
            self::Awarded => 'Premiación completa',
        };
    }
}
