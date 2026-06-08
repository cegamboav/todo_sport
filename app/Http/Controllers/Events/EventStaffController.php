<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\Auth\AdminAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventStaffController extends Controller
{
    public function store(Request $request, Event $event, AdminAccessService $access): RedirectResponse
    {
        $this->authorize('manageStaff', $event);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $access->assignUserToEvent($event, $user, $request->user());

        return redirect()
            ->route('events.config.staff', $event)
            ->with('success', "Usuario {$user->username} asignado al evento.");
    }

    public function destroy(Request $request, Event $event, User $user): RedirectResponse
    {
        $this->authorize('manageStaff', $event);

        $event->eventStaff()->where('user_id', $user->id)->delete();

        return redirect()
            ->route('events.config.staff', $event)
            ->with('success', 'Asignación removida.');
    }
}
