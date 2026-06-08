<?php

namespace App\Support;

use App\Models\OperationalSession;
use Illuminate\Http\JsonResponse;

class OperationalSessionConflictException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?OperationalSession $session = null,
        public readonly string $codeKey = 'OPERATIONAL_SESSION_CONFLICT',
    ) {
        parent::__construct($message);
    }

    public static function forSession(OperationalSession $session): self
    {
        $owner = $session->user?->username ?? 'otro usuario';

        return new self(
            message: "{$owner} está editando este recurso.",
            session: $session,
        );
    }

    public static function missingSession(): self
    {
        return new self(
            message: 'Debes iniciar una sesión de edición antes de guardar.',
            codeKey: 'OPERATIONAL_SESSION_REQUIRED',
        );
    }

    public static function expiredSession(): self
    {
        return new self(
            message: 'La sesión de edición expiró. Vuelve a abrir la pantalla.',
            codeKey: 'OPERATIONAL_SESSION_EXPIRED',
        );
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->codeKey,
            'session_owner' => $this->session?->user?->username,
            'session_id' => $this->session?->id,
        ], 409);
    }
}
