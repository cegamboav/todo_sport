<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\License\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LicenseController extends Controller
{
    // Root view: app.blade.php → app.ts (admin/staff shell)

    public function show(LicenseService $licenseService): Response|HttpResponse
    {
        $this->authorizeAdmin();

        return Inertia::render('Admin/License/Show', [
            'license' => $licenseService->state()->toArray(),
        ]);
    }

    public function import(Request $request, LicenseService $licenseService): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'license_file' => ['required', 'file', 'mimes:json,txt', 'max:2048'],
        ]);

        $licenseService->import($request->file('license_file'), $request->user()?->id);

        return redirect()
            ->route('admin.license.show')
            ->with('success', 'Licencia importada (validación criptográfica pendiente L1).');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
