<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Professor = 'professor';
    case Mesa = 'mesa';
    case Corner = 'corner';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Staff => 'Staff de evento',
            self::Professor => 'Profesor',
            self::Mesa => 'Mesa',
            self::Corner => 'Juez de esquina',
        };
    }
}
