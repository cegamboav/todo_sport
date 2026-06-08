<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreModalityRequest;
use App\Models\Modality;
use App\Services\Events\ModalityCatalogService;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModalityController extends Controller
{
    public function index(Request $request, ModalityCatalogService $catalog): Response
    {
        $this->authorize('viewAny', Modality::class);

        return Inertia::render('Events/Modalities/Index', [
            'modalities' => InertiaPaginator::present($catalog->paginate($request->only(['search', 'per_page']))),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Modality::class);

        return Inertia::render('Events/Modalities/Create');
    }

    public function store(StoreModalityRequest $request, ModalityCatalogService $catalog): RedirectResponse
    {
        $this->authorize('create', Modality::class);

        $catalog->create($request->validated());

        return redirect()
            ->route('config.modalities.index')
            ->with('success', 'Modalidad creada.');
    }

    public function edit(Modality $modality): Response
    {
        $this->authorize('update', $modality);

        return Inertia::render('Events/Modalities/Edit', [
            'modality' => $modality,
        ]);
    }

    public function update(StoreModalityRequest $request, Modality $modality, ModalityCatalogService $catalog): RedirectResponse
    {
        $this->authorize('update', $modality);

        $catalog->update($modality, $request->validated());

        return redirect()
            ->route('config.modalities.index')
            ->with('success', 'Modalidad actualizada.');
    }
}
