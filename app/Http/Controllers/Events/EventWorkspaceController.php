<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Events\EventWorkspacePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventWorkspaceController extends Controller
{
    public function overview(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Overview', $presenter->shared($request, $event));
    }

    public function participants(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Participants', $presenter->shared($request, $event));
    }

    public function configModalities(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Config/Modalities', $presenter->shared($request, $event));
    }

    public function configCombos(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Config/Combos', $presenter->shared($request, $event));
    }

    public function configStaff(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Config/Staff', $presenter->shared($request, $event));
    }

    public function configSettings(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Config/Settings', $presenter->shared($request, $event));
    }

    public function redirectLegacyHub(Request $request, Event $event): RedirectResponse
    {
        $map = [
            'overview' => 'events.show',
            'participants' => 'events.participants',
            'categories' => 'events.categories',
            'registrations' => 'events.participants',
            'modalities' => 'events.config.modalities',
            'combos' => 'events.config.combos',
            'staff' => 'events.config.staff',
            'settings' => 'events.config.settings',
        ];

        $tab = (string) $request->query('tab', 'participants');

        return redirect()->route($map[$tab] ?? 'events.participants', $event);
    }
}
