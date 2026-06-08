<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreProfessorRequest;
use App\Http\Requests\Masters\UpdateProfessorRequest;
use App\Models\Grade;
use App\Models\Professor;
use App\Services\Masters\ProfessorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\InertiaPaginator;
use Inertia\Inertia;
use Inertia\Response;

class ProfessorController extends Controller
{
    public function index(Request $request, ProfessorService $professorService): Response
    {
        $this->authorize('viewAny', Professor::class);

        return Inertia::render('Masters/Professors/Index', [
            'professors' => InertiaPaginator::present($professorService->paginate($request->only([
                'search',
                'grade_id',
                'only_trashed',
                'with_trashed',
                'per_page',
            ]))),
            'filters' => $request->only(['search', 'grade_id', 'only_trashed']),
            'gradeOptions' => $professorService->gradeOptions(),
        ]);
    }

    public function create(ProfessorService $professorService): Response
    {
        $this->authorize('create', Professor::class);

        return Inertia::render('Masters/Professors/Create', [
            'gradeOptions' => $professorService->gradeOptions(),
            'defaultGradeId' => Grade::query()->where('name', 'I Dan')->value('id'),
        ]);
    }

    public function store(StoreProfessorRequest $request, ProfessorService $professorService): RedirectResponse
    {
        $this->authorize('create', Professor::class);

        $professorService->create($request->validated(), $request->user());

        return redirect()
            ->route('masters.professors.index')
            ->with('success', 'Profesor creado correctamente.');
    }

    public function edit(Professor $professor, ProfessorService $professorService): Response
    {
        $this->authorize('update', $professor);

        return Inertia::render('Masters/Professors/Edit', [
            'professor' => $professor->load(['grade', 'user:id,username']),
            'systemAccess' => $professor->user
                ? ['username' => $professor->user->username]
                : null,
            'gradeOptions' => $professorService->gradeOptions(),
        ]);
    }

    public function update(UpdateProfessorRequest $request, Professor $professor, ProfessorService $professorService): RedirectResponse
    {
        $this->authorize('update', $professor);

        $professorService->update($professor, $request->validated(), $request->user());

        return redirect()
            ->route('masters.professors.index')
            ->with('success', 'Profesor actualizado correctamente.');
    }

    public function destroy(Professor $professor, ProfessorService $professorService): RedirectResponse
    {
        $this->authorize('delete', $professor);

        $professorService->deactivate($professor, request()->user());

        return redirect()
            ->route('masters.professors.index')
            ->with('success', 'Profesor desactivado correctamente.');
    }

    public function restore(Professor $professor, ProfessorService $professorService): RedirectResponse
    {
        $this->authorize('restore', $professor);

        $professorService->restore($professor, request()->user());

        return redirect()
            ->route('masters.professors.index')
            ->with('success', 'Profesor restaurado correctamente.');
    }
}
