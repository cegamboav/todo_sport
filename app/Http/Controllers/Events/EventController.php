<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\TransitionEventStatusRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Enums\MasterStatus;
use App\Models\School;
use App\Services\Events\EventService;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, EventService $eventService): Response
    {
        $this->authorize('viewAny', Event::class);

        return Inertia::render('Events/Index', [
            'events' => InertiaPaginator::present($eventService->paginate($request->only([
                'search',
                'status',
                'per_page',
            ]))),
            'filters' => $request->only(['search', 'status']),
            'statusOptions' => collect(EventStatus::cases())->map(fn (EventStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'schoolOptions' => School::query()
                ->where('status', MasterStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function store(StoreEventRequest $request, EventService $eventService): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $event = $eventService->create($request->validated(), $request->user());

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Evento creado. Configura modalidades, combos e inscripciones.');
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $event->load('hostSchool:id,name,abbreviation'),
            'schoolOptions' => School::query()
                ->where('status', MasterStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name', 'abbreviation']),
            'statusOptions' => collect(EventStatus::cases())->map(fn (EventStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorize('update', $event);

        $eventService->update($event, $request->validated(), $request->user());

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Evento actualizado.');
    }

    public function destroy(Event $event, EventService $eventService): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()
            ->route('events.index')
            ->with('success', 'Evento eliminado.');
    }

    public function transitionStatus(
        TransitionEventStatusRequest $request,
        Event $event,
        EventService $eventService,
    ): RedirectResponse {
        $this->authorize('transitionStatus', $event);

        $eventService->transitionStatus(
            $event,
            $request->enum('status', EventStatus::class),
            $request->user(),
        );

        return back()->with('success', 'Estado del evento actualizado.');
    }
}
