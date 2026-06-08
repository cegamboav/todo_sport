<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case PendingPasswordChange = 'pending_password_change';

    public function allowsLogin(): bool
    {
        return in_array($this, [self::Active, self::PendingPasswordChange], true);
    }

    public function message(): ?string
    {
        return match ($this) {
            self::Inactive => 'Tu cuenta está inactiva.',
            self::Blocked => 'Tu cuenta está bloqueada.',
            default => null,
        };
    }
}
