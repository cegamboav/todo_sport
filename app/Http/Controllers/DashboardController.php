<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Auth\AdminAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminAccessService $adminAccess,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($this->adminAccess->isMesa($user)) {
            return Inertia::location(route('rings.home'));
        }

        $events = Event::query()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'name', 'status']);

        $access = $this->adminAccess->sharedAccessState($user);

        return Inertia::render('Admin/Dashboard', [
            'events' => $events,
            'role' => $user->role->value,
            'canAccessMasters' => $access['can_access_masters'],
            'activeEventStaff' => $access['active_event_staff'],
        ]);
    }
}
