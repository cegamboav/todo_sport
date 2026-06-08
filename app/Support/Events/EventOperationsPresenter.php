<?php

namespace App\Support\Events;

use App\Enums\EventStatus;
use App\Enums\RegistrationItemStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Services\Auth\AdminAccessService;
use App\Services\Events\EventService;
use Illuminate\Http\Request;

class EventOperationsPresenter
{
    public function __construct(
        private readonly EventService $events,
        private readonly AdminAccessService $access,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function shared(Request $request, Event $event): array
    {
        $event = $this->events->loadHub($event);
        $user = $request->user();

        $pendingItems = 0;
        foreach ($event->eventCompetitors as $participant) {
            foreach ($participant->registrationItems as $item) {
                if ($item->status === RegistrationItemStatus::Pending && $item->is_billable) {
                    $pendingItems++;
                }
            }
        }

        return [
            'event' => $event->only(['id', 'name', 'status', 'event_date', 'venue']),
            'operations' => [
                'title' => $event->name,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'event_date' => $event->event_date?->format('Y-m-d'),
                'venue' => $event->venue,
                'summary' => [
                    'participants' => $event->eventCompetitors->count(),
                    'pending_charges' => $pendingItems,
                ],
            ],
            'canAccessEventAdmin' => $this->access->canAccessEventAdminWorkspace($user, $event),
            'canAccessEventOperations' => $this->access->canAccessEventOperationsWorkspace($user, $event),
            'canManagePayments' => $this->access->canManageEvent($user, $event),
            'isAdmin' => $user->role === UserRole::Admin,
            'operationalModules' => [
                [
                    'id' => 'caja',
                    'label' => 'Caja',
                    'description' => 'Validar pagos, marcar cobros y venta de entradas.',
                    'status' => 'coming_soon',
                ],
                [
                    'id' => 'check-in',
                    'label' => 'Check-in',
                    'description' => 'Validar acceso de competidores y staff al venue.',
                    'status' => 'coming_soon',
                ],
                [
                    'id' => 'pagos',
                    'label' => 'Pagos pendientes',
                    'description' => 'Cola operacional de cobros del torneo.',
                    'status' => 'coming_soon',
                ],
                [
                    'id' => 'validacion',
                    'label' => 'Validación',
                    'description' => 'Control operativo de inscripciones y acceso.',
                    'status' => 'coming_soon',
                ],
            ],
        ];
    }
}
