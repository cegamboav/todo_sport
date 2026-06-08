<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\AssignCategoryCompetitorRequest;
use App\Http\Requests\Events\StoreCategoryMatchRequest;
use App\Http\Requests\Events\StoreEventCategoryRequest;
use App\Http\Requests\Events\SyncCategoryMatchesRequest;
use App\Http\Requests\Events\SyncCategoryOrderRequest;
use App\Http\Requests\Events\UpdateEventCategoryStatusRequest;
use App\Models\CategoryCompetitor;
use App\Models\CategoryMatch;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventCompetitor;
use App\Services\Competitive\EventCategoryService;
use App\Support\Events\EventWorkspacePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventCategoryController extends Controller
{
    public function index(Request $request, Event $event, EventWorkspacePresenter $presenter): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Workspace/Categories/Index', $presenter->categories($request, $event));
    }

    public function show(
        Request $request,
        Event $event,
        EventCategory $category,
        EventWorkspacePresenter $presenter,
    ): Response {
        $this->authorize('view', $event);
        abort_unless($category->event_id === $event->id, 404);

        return Inertia::render('Events/Workspace/Categories/Show', $presenter->categoryShow($request, $event, $category));
    }

    public function store(
        StoreEventCategoryRequest $request,
        Event $event,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);

        try {
            $category = $categories->create($event, $request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('events.categories.show', [$event, $category])
            ->with('success', "Categoría {$category->internal_code} creada.");
    }

    public function update(
        StoreEventCategoryRequest $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        try {
            $categories->update($category, $request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('events.categories', $event)
            ->with('success', 'Categoría actualizada.');
    }

    public function updateStatus(
        UpdateEventCategoryStatusRequest $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        try {
            $categories->updateStatus(
                $category,
                $request->enum('status', \App\Enums\EventCategoryStatus::class),
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Estado de categoría actualizado.');
    }

    public function syncOrder(
        SyncCategoryOrderRequest $request,
        Event $event,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);

        $categories->syncCompetitionOrder($event, $request->validated('rows'), $request->user());

        return back()->with('success', 'Orden de competencia guardado.');
    }

    public function destroy(
        Request $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        try {
            $categories->delete($category, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('events.categories', $event)
            ->with('success', 'Categoría eliminada.');
    }

    public function assignCompetitor(
        AssignCategoryCompetitorRequest $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        $participant = EventCompetitor::query()->findOrFail($request->integer('event_competitor_id'));

        try {
            $categories->assignCompetitor($category, $participant, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Competidor agregado a la categoría.');
    }

    public function removeCompetitor(
        Request $request,
        Event $event,
        EventCategory $category,
        CategoryCompetitor $assignment,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);
        abort_unless($assignment->event_category_id === $category->id, 404);

        try {
            $categories->removeCompetitor($assignment, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Competidor removido de la categoría.');
    }

    public function storeMatch(
        StoreCategoryMatchRequest $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        try {
            $categories->addMatch(
                $category,
                $request->integer('red_event_competitor_id') ?: null,
                $request->integer('blue_event_competitor_id') ?: null,
                (string) $request->input('stage_label', 'R1'),
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Combate agregado a la llave manual.');
    }

    public function destroyMatch(
        Request $request,
        Event $event,
        EventCategory $category,
        CategoryMatch $match,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);
        abort_unless($match->event_category_id === $category->id, 404);

        try {
            $categories->removeMatch($match, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Combate removido de la llave manual.');
    }

    public function syncMatches(
        SyncCategoryMatchesRequest $request,
        Event $event,
        EventCategory $category,
        EventCategoryService $categories,
    ): RedirectResponse {
        $this->authorize('update', $event);
        abort_unless($category->event_id === $event->id, 404);

        try {
            $categories->syncMatches($category, $request->validated('rows'), $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Llave manual guardada.');
    }
}
