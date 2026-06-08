<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAdminDashboardAccess;
use App\Http\Middleware\EnsureLicense;
use App\Http\Middleware\EnsureMastersPanelAccess;
use App\Http\Middleware\EnsureProfessorPortalAccess;
use App\Http\Middleware\EnsureRingsAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Support\OperationalSessionConflictException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'license' => EnsureLicense::class,
            'inertia' => HandleInertiaRequests::class,
            'guest' => RedirectIfAuthenticated::class,
            'masters.panel' => EnsureMastersPanelAccess::class,
            'admin.dashboard' => EnsureAdminDashboardAccess::class,
            'rings.access' => EnsureRingsAccess::class,
            'professor.portal' => EnsureProfessorPortalAccess::class,
            'judge.access' => \App\Http\Middleware\EnsureJudgeAppAccess::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (OperationalSessionConflictException $exception) {
            return $exception->toResponse();
        });
    })->create();
