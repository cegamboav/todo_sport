<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreSchoolRequest;
use App\Http\Requests\Masters\UpdateSchoolRequest;
use App\Models\School;
use App\Services\Masters\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\InertiaPaginator;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request, SchoolService $schoolService): Response
    {
        $this->authorize('viewAny', School::class);

        return Inertia::render('Masters/Schools/Index', [
            'schools' => InertiaPaginator::present($schoolService->paginate($request->only([
                'search',
                'country',
                'city',
                'director_id',
                'only_trashed',
                'with_trashed',
                'per_page',
            ]))),
            'filters' => $request->only(['search', 'country', 'city', 'director_id', 'only_trashed']),
        ]);
    }

    public function create(SchoolService $schoolService): Response
    {
        $this->authorize('create', School::class);

        return Inertia::render('Masters/Schools/Create', [
            'directorOptions' => $schoolService->directorOptions(),
        ]);
    }

    public function store(StoreSchoolRequest $request, SchoolService $schoolService): RedirectResponse
    {
        $this->authorize('create', School::class);

        $schoolService->create($request->validated(), $request->user());

        return redirect()
            ->route('masters.schools.index')
            ->with('success', 'Escuela creada correctamente.');
    }

    public function edit(School $school, SchoolService $schoolService): Response
    {
        $this->authorize('update', $school);

        return Inertia::render('Masters/Schools/Edit', [
            'school' => $school->load('director'),
            'directorOptions' => $schoolService->directorOptions(),
        ]);
    }

    public function update(UpdateSchoolRequest $request, School $school, SchoolService $schoolService): RedirectResponse
    {
        $this->authorize('update', $school);

        $schoolService->update($school, $request->validated(), $request->user());

        return redirect()
            ->route('masters.schools.index')
            ->with('success', 'Escuela actualizada correctamente.');
    }

    public function destroy(School $school, SchoolService $schoolService): RedirectResponse
    {
        $this->authorize('delete', $school);

        $schoolService->deactivate($school, request()->user());

        return redirect()
            ->route('masters.schools.index')
            ->with('success', 'Escuela desactivada correctamente.');
    }

    public function restore(School $school, SchoolService $schoolService): RedirectResponse
    {
        $this->authorize('restore', $school);

        $schoolService->restore($school, request()->user());

        return redirect()
            ->route('masters.schools.index')
            ->with('success', 'Escuela restaurada correctamente.');
    }
}
