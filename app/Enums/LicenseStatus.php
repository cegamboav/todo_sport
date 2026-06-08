<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Grace = 'grace';
    case Expired = 'expired';
    case Invalid = 'invalid';
    case Missing = 'missing';

    public function isOperational(): bool
    {
        return in_array($this, [self::Active, self::Grace], true);
    }

    public function banner(): ?string
    {
        return match ($this) {
            self::Grace => 'warning',
            self::Expired, self::Invalid, self::Missing => 'error',
            default => null,
        };
    }
}
