<?php

namespace App\Enums;

enum RefereeSpecialty: string
{
    case Table = 'table';
    case Corner = 'corner';

    public function label(): string
    {
        return match ($this) {
            self::Table => 'Mesa',
            self::Corner => 'Juez de esquina',
        };
    }

    public function systemRole(): UserRole
    {
        return match ($this) {
            self::Table => UserRole::Mesa,
            self::Corner => UserRole::Corner,
        };
    }
}
