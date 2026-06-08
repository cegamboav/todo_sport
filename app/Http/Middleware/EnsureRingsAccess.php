<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRingsAccess
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->adminAccess->canAccessRingsModule($user)) {
            abort(403, 'No tienes acceso al módulo de rings.');
        }

        return $next($request);
    }
}
