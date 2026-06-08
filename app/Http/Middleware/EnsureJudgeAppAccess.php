<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJudgeAppAccess
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->adminAccess->isCornerJudge($user)) {
            abort(403, 'Acceso reservado a la app de juez de esquina.');
        }

        return $next($request);
    }
}
