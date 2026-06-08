<?php

namespace App\Http\Controllers\Rings;

use App\Http\Controllers\Controller;
use App\Models\Ring;
use App\Services\Auth\AdminAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function index(Request $request): Response|HttpResponse
    {
        $user = $request->user();

        abort_unless($this->adminAccess->canAccessRingsModule($user), 403);

        $rings = Ring::query()
            ->with('event:id,name,status')
            ->orderBy('name')
            ->get(['id', 'event_id', 'name', 'status']);

        return Inertia::render('Rings/Home', [
            'rings' => $rings,
        ]);
    }
}
