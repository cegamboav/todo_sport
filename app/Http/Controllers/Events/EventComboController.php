<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventComboRequest;
use App\Models\Event;
use App\Models\EventCombo;
use App\Services\Events\EventComboService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventComboController extends Controller
{
    public function store(StoreEventComboRequest $request, Event $event, EventComboService $comboService): RedirectResponse
    {
        $this->authorize('update', $event);

        $data = $request->validated();
        $comboService->create(
            $event,
            $data,
            $data['modality_ids'],
            $request->user(),
        );

        return back()->with('success', 'Combo creado.');
    }

    public function update(
        StoreEventComboRequest $request,
        Event $event,
        EventCombo $combo,
        EventComboService $comboService,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($combo->event_id === $event->id, 404);

        $data = $request->validated();
        $comboService->update($combo, $data, $data['modality_ids'], $request->user());

        return back()->with('success', 'Combo actualizado.');
    }

    public function destroy(
        Request $request,
        Event $event,
        EventCombo $combo,
        EventComboService $comboService,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($combo->event_id === $event->id, 404);

        $comboService->delete($combo, $request->user());

        return back()->with('success', 'Combo eliminado.');
    }
}
