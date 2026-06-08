<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\SyncEventModalitiesRequest;
use App\Models\Event;
use App\Services\Events\EventService;
use Illuminate\Http\RedirectResponse;

class EventModalityController extends Controller
{
    public function sync(SyncEventModalitiesRequest $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorize('update', $event);

        $eventService->syncEventModalities($event, $request->validated('modalities'), $request->user());

        return back()->with('success', 'Modalidades del evento guardadas.');
    }
}
