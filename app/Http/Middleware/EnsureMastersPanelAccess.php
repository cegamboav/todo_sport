<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMastersPanelAccess
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->adminAccess->canAccessMastersModule($user)) {
            abort(403, 'No tienes acceso a maestros. Se requiere rol admin o asignación staff en un evento abierto.');
        }

        return $next($request);
    }
}
