<?php

namespace App\Http\Middleware;

use App\Services\License\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicense
{
    public function __construct(
        private readonly LicenseService $licenseService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        // Placeholder S0: allow all requests; degraded rules in L2.
        $this->licenseService->state();

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        if ($request->routeIs('login', 'judge.login', 'logout', 'up')) {
            return true;
        }

        if ($request->is('admin/license*')) {
            return true;
        }

        return false;
    }
}
