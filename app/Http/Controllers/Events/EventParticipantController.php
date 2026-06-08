<?php



namespace App\Http\Controllers\Events;



use App\Http\Controllers\Controller;

use App\Http\Requests\Events\StoreEventParticipantRequest;

use App\Http\Requests\Events\StoreEventQuickCompetitorRequest;

use App\Http\Requests\Events\StoreEventQuickRegistrationRequest;

use App\Http\Requests\Events\StoreRegistrationItemRequest;

use App\Http\Requests\Events\UpdateRegistrationItemStatusRequest;

use App\Models\Event;

use App\Models\EventCombo;

use App\Models\EventCompetitor;

use App\Models\EventModality;

use App\Models\RegistrationItem;

use App\Services\Events\EventParticipationService;

use App\Enums\RegistrationItemStatus;

use Illuminate\Http\RedirectResponse;

use Illuminate\Validation\ValidationException;



class EventParticipantController extends Controller

{

    public function store(

        StoreEventParticipantRequest $request,

        Event $event,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);



        $result = $participation->addParticipant(

            $event,

            (int) $request->validated('competitor_id'),

            $request->validated('notes'),

            $request->user(),

        );



        $message = $result['created']

            ? 'Competidor agregado al evento.'

            : 'Ese competidor ya estaba inscrito en el evento.';



        return redirect()

            ->route('events.participants', $event)

            ->with($result['created'] ? 'success' : 'error', $message);

    }



    public function quickCreate(

        StoreEventQuickCompetitorRequest $request,

        Event $event,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);



        try {

            $result = $participation->quickCreateAndEnroll(

                $event,

                $request->validated(),

                $request->user(),

            );

        } catch (ValidationException $exception) {

            return redirect()

                ->route('events.participants', $event)

                ->withErrors($exception->errors())

                ->withInput();

        }



        return redirect()

            ->route('events.participants', $event)

            ->with('success', 'Competidor creado. Elige modalidades para completar la inscripción.')

            ->with('quick_register', [

                'id' => $result['competitor_id'],

                'label' => $result['competitor_label'],

            ]);

    }



    public function quickRegister(

        StoreEventQuickRegistrationRequest $request,

        Event $event,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);



        try {

            $result = $participation->quickRegister(

                $event,

                (int) $request->validated('competitor_id'),

                $request->input('event_modality_ids', []),

                $request->input('event_combo_ids', []),

                $request->validated('notes'),

                $request->user(),

            );

        } catch (ValidationException $exception) {

            return redirect()

                ->route('events.participants', $event)

                ->withErrors($exception->errors())

                ->withInput();

        }



        $parts = [];

        if ($result['participant_created']) {

            $parts[] = 'competidor agregado al evento';

        }

        if ($result['items_created'] > 0) {

            $parts[] = "{$result['items_created']} inscripción(es) registrada(s)";

        }

        if ($result['items_skipped'] > 0) {

            $parts[] = "{$result['items_skipped']} omitida(s) (ya inscritas)";

        }



        $message = $parts !== []

            ? 'Inscripción rápida: '.implode(', ', $parts).'.'

            : 'Competidor agregado al evento (sin modalidades seleccionadas).';



        return redirect()

            ->route('events.participants', $event)

            ->with('success', $message);

    }



    public function withdraw(

        Event $event,

        EventCompetitor $participant,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);

        abort_unless($participant->event_id === $event->id, 404);



        try {

            $participation->withdrawParticipant($participant, request()->user());

        } catch (ValidationException $exception) {

            return redirect()

                ->route('events.participants', $event)

                ->withErrors($exception->errors());

        }



        return redirect()

            ->route('events.participants', $event)

            ->with('success', 'Competidor desinscrito del evento. Modalidades y cobros asociados fueron removidos.');

    }



    public function storeRegistrationItem(

        StoreRegistrationItemRequest $request,

        Event $event,

        EventCompetitor $participant,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);

        abort_unless($participant->event_id === $event->id, 404);



        $isBillable = $request->boolean('is_billable', true);

        $allowOverride = $request->boolean('allow_duplicate_override', false);



        try {

            if ($request->validated('item_type') === 'modality') {

                $eventModality = EventModality::query()->findOrFail($request->integer('event_modality_id'));

                $participation->registerModality(

                    $participant,

                    $eventModality,

                    $request->user(),

                    $isBillable,

                    $allowOverride,

                );

            } else {

                $combo = EventCombo::query()->findOrFail($request->integer('event_combo_id'));

                $participation->registerCombo(

                    $participant,

                    $combo,

                    $request->user(),

                    $isBillable,

                    $allowOverride,

                );

            }

        } catch (ValidationException $exception) {

            return redirect()

                ->route('events.participants', $event)

                ->withErrors($exception->errors());

        }



        return redirect()

            ->route('events.participants', $event)

            ->with('success', $isBillable ? 'Inscripción y cobro registrados.' : 'Inscripción registrada sin cobro.');

    }

    public function destroyRegistrationItem(
        Event $event,
        RegistrationItem $registrationItem,
        EventParticipationService $participation,
    ): RedirectResponse {
        $this->authorize('update', $event);

        $registrationItem->load('eventCompetitor');
        abort_unless($registrationItem->eventCompetitor?->event_id === $event->id, 404);

        $participation->removeRegistrationItem($registrationItem, request()->user());

        return redirect()
            ->route('events.participants', $event)
            ->with('success', 'Inscripción eliminada.');
    }

    public function updateRegistrationItemStatus(

        UpdateRegistrationItemStatusRequest $request,

        Event $event,

        RegistrationItem $registrationItem,

        EventParticipationService $participation,

    ): RedirectResponse {

        $this->authorize('update', $event);



        $registrationItem->load('eventCompetitor');

        abort_unless($registrationItem->eventCompetitor?->event_id === $event->id, 404);



        $participation->updateItemStatus(

            $registrationItem,

            $request->enum('status', RegistrationItemStatus::class),

            $request->user(),

        );



        return redirect()

            ->route('events.participants', $event)

            ->with('success', 'Estado de inscripción actualizado.');

    }

}


