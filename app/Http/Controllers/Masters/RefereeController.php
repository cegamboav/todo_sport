<?php

namespace App\Http\Controllers\Masters;

use App\Enums\RefereeSpecialty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreRefereeRequest;
use App\Http\Requests\Masters\UpdateRefereeRequest;
use App\Models\Referee;
use App\Services\Masters\RefereeService;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefereeController extends Controller
{
    public function index(Request $request, RefereeService $refereeService): Response
    {
        $this->authorize('viewAny', Referee::class);

        return Inertia::render('Masters/Referees/Index', [
            'referees' => InertiaPaginator::present($refereeService->paginate($request->only([
                'search',
                'specialty',
                'grade_id',
                'only_trashed',
                'with_trashed',
                'per_page',
            ]))),
            'filters' => $request->only(['search', 'specialty', 'grade_id', 'only_trashed']),
            'specialtyOptions' => collect(RefereeSpecialty::cases())->map(fn (RefereeSpecialty $specialty) => [
                'value' => $specialty->value,
                'label' => $specialty->label(),
            ])->values(),
            'gradeOptions' => $refereeService->gradeOptions(),
        ]);
    }

    public function create(RefereeService $refereeService): Response
    {
        $this->authorize('create', Referee::class);

        return Inertia::render('Masters/Referees/Create', [
            'gradeOptions' => $refereeService->gradeOptions(),
            'specialtyOptions' => collect(RefereeSpecialty::cases())->map(fn (RefereeSpecialty $specialty) => [
                'value' => $specialty->value,
                'label' => $specialty->label(),
            ])->values(),
        ]);
    }

    public function store(StoreRefereeRequest $request, RefereeService $refereeService): RedirectResponse
    {
        $this->authorize('create', Referee::class);

        $refereeService->create($request->validated(), $request->user());

        return redirect()
            ->route('masters.referees.index')
            ->with('success', 'Árbitro creado correctamente.');
    }

    public function edit(Referee $referee, RefereeService $refereeService): Response
    {
        $this->authorize('update', $referee);

        return Inertia::render('Masters/Referees/Edit', [
            'referee' => $referee->load(['grade', 'user:id,username']),
            'systemAccess' => $referee->user
                ? ['username' => $referee->user->username]
                : null,
            'gradeOptions' => $refereeService->gradeOptions(),
            'specialtyOptions' => collect(RefereeSpecialty::cases())->map(fn (RefereeSpecialty $specialty) => [
                'value' => $specialty->value,
                'label' => $specialty->label(),
            ])->values(),
        ]);
    }

    public function update(UpdateRefereeRequest $request, Referee $referee, RefereeService $refereeService): RedirectResponse
    {
        $this->authorize('update', $referee);

        $refereeService->update($referee, $request->validated(), $request->user());

        return redirect()
            ->route('masters.referees.index')
            ->with('success', 'Árbitro actualizado correctamente.');
    }

    public function destroy(Referee $referee, RefereeService $refereeService): RedirectResponse
    {
        $this->authorize('delete', $referee);

        $refereeService->deactivate($referee, request()->user());

        return redirect()
            ->route('masters.referees.index')
            ->with('success', 'Árbitro desactivado correctamente.');
    }

    public function restore(Referee $referee, RefereeService $refereeService): RedirectResponse
    {
        $this->authorize('restore', $referee);

        $refereeService->restore($referee, request()->user());

        return redirect()
            ->route('masters.referees.index')
            ->with('success', 'Árbitro restaurado correctamente.');
    }
}
