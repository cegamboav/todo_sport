<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Auth\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    public static function pathFor(User $user): string
    {
        $access = app(AdminAccessService::class);

        return match ($user->role) {
            UserRole::Admin => route('dashboard', absolute: false),
            UserRole::Staff => route('dashboard', absolute: false),
            UserRole::Mesa => route('rings.home', absolute: false),
            UserRole::Professor => route('professor.home', absolute: false),
            UserRole::Corner => route('judge.home', absolute: false),
        };
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
