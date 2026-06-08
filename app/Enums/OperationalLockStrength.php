<?php

namespace App\Enums;

enum OperationalLockStrength: string
{
    case Awareness = 'awareness';
    case Soft = 'soft';
    case Strong = 'strong';
}
