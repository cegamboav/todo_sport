<?php

namespace App\Http\Middleware;

use App\Services\License\LicenseService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly \App\Services\Auth\AdminAccessService $adminAccess,
    ) {}

    /**
     * Multi-root Inertia shells — each loads a dedicated Vite entry (see resources/views/*.blade.php).
     */
    public function rootView(Request $request): string
    {
        if ($request->is('rings', 'rings/*')) {
            return 'rings';
        }

        if ($request->is('judge', 'judge/*')) {
            return 'judge';
        }

        if ($request->is('school', 'school/*')) {
            return 'professor';
        }

        return 'app';
    }

  /**
   * @return array<string, mixed>
   */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'username' => $request->user()->username,
                    'role' => $request->user()->role->value,
                    'status' => $request->user()->status->value,
                ] : null,
                'access' => $request->user()
                    ? $this->adminAccess->sharedAccessState($request->user())
                    : null,
            ],
            'license' => $this->licenseService->state()->toArray(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'quick_register' => fn () => $request->session()->get('quick_register'),
            ],
        ]);
    }
}
