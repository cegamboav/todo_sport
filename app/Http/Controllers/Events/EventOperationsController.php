<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Events\EventOperationsPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventOperationsController extends Controller
{
    /**
     * Event Operations Workspace — caja, check-in, pagos (S2C foundation).
     */
    public function index(Request $request, Event $event, EventOperationsPresenter $presenter): Response
    {
        $this->authorize('view', $event);

        $props = $presenter->shared($request, $event);

        abort_unless(
            $props['canAccessEventOperations'],
            403,
            'No tienes acceso al workspace operacional de este torneo.',
        );

        return Inertia::render('Events/Operations/Index', $props);
    }
}
