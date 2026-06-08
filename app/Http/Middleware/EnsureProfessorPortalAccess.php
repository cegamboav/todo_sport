<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessorPortalAccess
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->adminAccess->isProfessor($user)) {
            abort(403, 'Acceso reservado al portal de profesores.');
        }

        return $next($request);
    }
}
