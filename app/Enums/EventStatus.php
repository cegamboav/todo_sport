<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Operational = 'operational';
    case Finished = 'finished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::RegistrationOpen => 'Inscripción abierta',
            self::RegistrationClosed => 'Inscripción cerrada',
            self::Operational => 'Operativo',
            self::Finished => 'Finalizado',
            self::Archived => 'Archivado',
        };
    }

    /** @return list<self> */
    public static function staffAssignmentStatuses(): array
    {
        return [
            self::RegistrationOpen,
            self::RegistrationClosed,
            self::Operational,
        ];
    }

    public function allowsStaffOperationalAccess(): bool
    {
        return in_array($this, [self::RegistrationOpen, self::RegistrationClosed, self::Operational], true);
    }

    public function allowsRingsOperationalAccess(): bool
    {
        return $this === self::Operational;
    }

    public function allowsRegistration(): bool
    {
        return $this === self::RegistrationOpen;
    }

    public function isEditableConfiguration(): bool
    {
        return in_array($this, [self::Draft, self::RegistrationOpen, self::RegistrationClosed], true);
    }

    /** @deprecated Use allowsRingsOperationalAccess() */
    public function isOpen(): bool
    {
        return $this->allowsRingsOperationalAccess();
    }
}
