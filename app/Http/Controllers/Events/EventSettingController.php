<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\UpdateEventSettingsRequest;
use App\Models\Event;
use App\Services\Events\EventService;
use Illuminate\Http\RedirectResponse;

class EventSettingController extends Controller
{
    public function update(UpdateEventSettingsRequest $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorize('update', $event);

        $eventService->updateSettings($event, $request->validated(), $request->user());

        return back()->with('success', 'Configuración operacional guardada.');
    }
}
