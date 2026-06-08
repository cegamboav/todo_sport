<?php

namespace App\Actions\Example;

use App\Enums\AuditSeverity;
use App\Models\User;
use App\Services\Audit\AuditService;

/**
 * Patrón Actions S0 — un caso de uso por clase, invocado desde controllers delgados.
 * Eliminar o reemplazar cuando existan acciones reales de dominio.
 */
class LogFoundationAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(User $actor, string $message): void
    {
        $this->audit->record(
            actor: $actor,
            eventType: 'foundation.example',
            severity: AuditSeverity::Info,
            entityType: 'user',
            entityId: $actor->id,
            summary: $message,
        );
    }
}
