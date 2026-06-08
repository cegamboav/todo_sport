<?php

namespace App\Enums;

enum RegistrationItemStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Waived => 'Exento',
        };
    }
}
