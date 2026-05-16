<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOperatorSkinRequest;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOperatorSkinController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage-content');

        $filters = $request->validate([
            'q'         => 'nullable|string|max:80',
            'operator'  => 'nullable|integer|exists:operators,id',
            'rarity'    => 'nullable|in:common,rare,epic,legendary',
            'status'    => 'nullable|in:pending,queued,generating,ready,failed',
        ]);

        $query = OperatorSkin::query()
            ->with('operator:id,slug,name,codename,faction,rarity')
            ->when($filters['q']        ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['operator'] ?? null, fn ($q, $v) => $q->where('operator_id', $v))
            ->when($filters['rarity']   ?? null, fn ($q, $v) => $q->where('rarity', $v))
            ->when($filters['status']   ?? null, fn ($q, $v) => $q->where('generation_status', $v))
            ->orderBy('operator_id')
            ->orderByDesc('is_default')
            ->orderBy('name');

        return Inertia::render('Admin/Skins/Index', [
            'skins'     => $query->paginate(25)->withQueryString(),
            'filters'   => (object) $filters,
            'operators' => Operator::query()
                ->orderBy('name')
                ->get(['id', 'name', 'codename', 'rarity']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage-content');

        return Inertia::render('Admin/Skins/Create', [
            'operators' => Operator::query()
                ->orderBy('name')
                ->get(['id', 'name', 'codename', 'faction', 'rarity']),
            'enums'     => $this->enums(),
        ]);
    }

    public function store(StoreOperatorSkinRequest $request): RedirectResponse
    {
        $skin = OperatorSkin::create($request->validated());

        return redirect()
            ->route('admin.skins.edit', $skin)
            ->with('status', "Skin {$skin->name} créé.");
    }

    public function edit(OperatorSkin $skin): Response
    {
        $this->authorize('manage-content');

        $skin->load('operator:id,slug,name,codename,faction,rarity,base_model_url,base_generation_status');

        return Inertia::render('Admin/Skins/Edit', [
            'skin'      => $skin,
            'operators' => Operator::query()
                ->orderBy('name')
                ->get(['id', 'name', 'codename', 'faction', 'rarity']),
            'enums'     => $this->enums(),
        ]);
    }

    public function update(StoreOperatorSkinRequest $request, OperatorSkin $skin): RedirectResponse
    {
        $skin->update($request->validated());

        return redirect()
            ->route('admin.skins.edit', $skin)
            ->with('status', "Skin {$skin->name} mis à jour.");
    }

    public function destroy(OperatorSkin $skin): RedirectResponse
    {
        $this->authorize('manage-content');

        $name = $skin->name;
        $skin->delete();

        return redirect()
            ->route('admin.skins.index')
            ->with('status', "Skin {$name} supprimé.");
    }

    public function generate(Request $request, MeshyGenerationService $service, OperatorSkin $skin): RedirectResponse
    {
        $this->authorize('manage-content');

        try {
            $taskId = $service->generate($skin, force: $request->boolean('force'));
        } catch (MeshyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Génération skin {$skin->name} lancée (task {$taskId}).");
    }

    private function enums(): array
    {
        return [
            'rarities' => OperatorSkin::RARITIES,
        ];
    }
}
