<?php

namespace App\Http\Controllers\Masters;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreCompetitorRequest;
use App\Http\Requests\Masters\UpdateCompetitorRequest;
use App\Models\Competitor;
use App\Services\Masters\CompetitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\InertiaPaginator;
use Inertia\Inertia;
use Inertia\Response;

class CompetitorController extends Controller
{
    public function index(Request $request, CompetitorService $competitorService): Response
    {
        $this->authorize('viewAny', Competitor::class);

        return Inertia::render('Masters/Competitors/Index', [
            'competitors' => InertiaPaginator::present($competitorService->paginate($request->only([
                'search',
                'gender',
                'school_id',
                'grade_id',
                'only_trashed',
                'with_trashed',
                'per_page',
            ]))),
            'filters' => $request->only(['search', 'gender', 'school_id', 'grade_id', 'only_trashed']),
            'genderOptions' => collect(Gender::cases())->map(fn (Gender $gender) => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])->values(),
            'schoolOptions' => $competitorService->schoolOptions(),
            'gradeOptions' => $competitorService->gradeOptions(),
        ]);
    }

    public function create(CompetitorService $competitorService): Response
    {
        $this->authorize('create', Competitor::class);

        return Inertia::render('Masters/Competitors/Create', [
            'schoolOptions' => $competitorService->schoolOptions(),
            'gradeOptions' => $competitorService->gradeOptions(),
            'genderOptions' => collect(Gender::cases())->map(fn (Gender $gender) => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])->values(),
        ]);
    }

    public function store(StoreCompetitorRequest $request, CompetitorService $competitorService): RedirectResponse
    {
        $this->authorize('create', Competitor::class);

        $competitorService->create($request->validated(), $request->user());

        return redirect()
            ->route('masters.competitors.index')
            ->with('success', 'Competidor creado correctamente.');
    }

    public function edit(Competitor $competitor, CompetitorService $competitorService): Response
    {
        $this->authorize('update', $competitor);

        return Inertia::render('Masters/Competitors/Edit', [
            'competitor' => $competitor->load(['school', 'grade']),
            'schoolOptions' => $competitorService->schoolOptions(),
            'gradeOptions' => $competitorService->gradeOptions(),
            'genderOptions' => collect(Gender::cases())->map(fn (Gender $gender) => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])->values(),
        ]);
    }

    public function update(UpdateCompetitorRequest $request, Competitor $competitor, CompetitorService $competitorService): RedirectResponse
    {
        $this->authorize('update', $competitor);

        $competitorService->update($competitor, $request->validated(), $request->user());

        return redirect()
            ->route('masters.competitors.index')
            ->with('success', 'Competidor actualizado correctamente.');
    }

    public function destroy(Competitor $competitor, CompetitorService $competitorService): RedirectResponse
    {
        $this->authorize('delete', $competitor);

        $competitorService->deactivate($competitor, request()->user());

        return redirect()
            ->route('masters.competitors.index')
            ->with('success', 'Competidor desactivado correctamente.');
    }

    public function restore(Competitor $competitor, CompetitorService $competitorService): RedirectResponse
    {
        $this->authorize('restore', $competitor);

        $competitorService->restore($competitor, request()->user());

        return redirect()
            ->route('masters.competitors.index')
            ->with('success', 'Competidor restaurado correctamente.');
    }
}
