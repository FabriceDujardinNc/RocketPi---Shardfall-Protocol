<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccessoryRequest;
use App\Models\Accessory;
use App\Models\Operator;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminAccessoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manage-content');

        $filters = $request->validate([
            'q'      => 'nullable|string|max:80',
            'slot'   => 'nullable|in:head,face,back,hands,legs',
            'rarity' => 'nullable|in:common,rare,epic,legendary',
            'status' => 'nullable|in:pending,queued,generating,ready,failed',
        ]);

        $query = Accessory::query()
            ->with('operators:id,slug,name,codename')
            ->when($filters['q']      ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['slot']   ?? null, fn ($q, $v) => $q->where('slot', $v))
            ->when($filters['rarity'] ?? null, fn ($q, $v) => $q->where('rarity', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('generation_status', $v))
            ->orderBy('slot')
            ->orderBy('name');

        return Inertia::render('Admin/Accessories/Index', [
            'accessories' => $query->paginate(25)->withQueryString(),
            'filters'     => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manage-content');

        return Inertia::render('Admin/Accessories/Create', [
            'operators' => Operator::query()
                ->orderBy('name')
                ->get(['id', 'name', 'codename', 'faction', 'rarity']),
            'enums'     => $this->enums(),
        ]);
    }

    public function store(StoreAccessoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $operatorIds = collect($validated['operator_ids'] ?? [])->map(fn ($v) => (int) $v);
        $defaultOperatorId = $validated['default_operator_id'] ?? null;
        unset($validated['operator_ids'], $validated['default_operator_id']);

        $accessory = DB::transaction(function () use ($validated, $operatorIds, $defaultOperatorId) {
            $accessory = Accessory::create($validated);
            $this->syncOperators($accessory, $operatorIds->all(), $defaultOperatorId);
            return $accessory;
        });

        return redirect()
            ->route('admin.accessories.edit', $accessory)
            ->with('status', "Accessoire {$accessory->name} créé.");
    }

    public function edit(Accessory $accessory): Response
    {
        $this->authorize('manage-content');

        $accessory->load('operators:id,slug,name,codename,faction,rarity');

        return Inertia::render('Admin/Accessories/Edit', [
            'accessory' => array_merge($accessory->toArray(), [
                'operator_ids'        => $accessory->operators->pluck('id')->all(),
                'default_operator_id' => optional(
                    $accessory->operators->firstWhere('pivot.is_default', true)
                )->id,
            ]),
            'operators' => Operator::query()
                ->orderBy('name')
                ->get(['id', 'name', 'codename', 'faction', 'rarity']),
            'enums'     => $this->enums(),
        ]);
    }

    public function update(StoreAccessoryRequest $request, Accessory $accessory): RedirectResponse
    {
        $validated = $request->validated();
        $operatorIds = collect($validated['operator_ids'] ?? [])->map(fn ($v) => (int) $v);
        $defaultOperatorId = $validated['default_operator_id'] ?? null;
        unset($validated['operator_ids'], $validated['default_operator_id']);

        DB::transaction(function () use ($accessory, $validated, $operatorIds, $defaultOperatorId) {
            $accessory->update($validated);
            $this->syncOperators($accessory, $operatorIds->all(), $defaultOperatorId);
        });

        return redirect()
            ->route('admin.accessories.edit', $accessory)
            ->with('status', "Accessoire {$accessory->name} mis à jour.");
    }

    public function destroy(Accessory $accessory): RedirectResponse
    {
        $this->authorize('manage-content');

        $name = $accessory->name;
        $accessory->delete();

        return redirect()
            ->route('admin.accessories.index')
            ->with('status', "Accessoire {$name} supprimé.");
    }

    public function generate(Request $request, MeshyGenerationService $service, Accessory $accessory): RedirectResponse
    {
        $this->authorize('manage-content');

        try {
            $taskId = $service->generate($accessory, force: $request->boolean('force'));
        } catch (MeshyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Génération accessoire {$accessory->name} lancée (task {$taskId}).");
    }

    /**
     * Synchronise les opérateurs liés à l'accessoire et marque l'un d'entre eux comme défaut.
     * Si $defaultOperatorId ne fait pas partie de $operatorIds, il est ignoré silencieusement.
     */
    private function syncOperators(Accessory $accessory, array $operatorIds, ?int $defaultOperatorId): void
    {
        $payload = [];
        foreach ($operatorIds as $opId) {
            $payload[$opId] = ['is_default' => $opId === $defaultOperatorId];
        }
        $accessory->operators()->sync($payload);
    }

    private function enums(): array
    {
        return [
            'slots'      => Accessory::SLOTS,
            'rarities'   => Accessory::RARITIES,
            'sockets'    => [
                'head'  => 'Head_Top',
                'face'  => 'Face_Front',
                'back'  => 'Back_Center',
                'hands' => 'Hand_R',
                'legs'  => 'Hip_R',
            ],
        ];
    }
}
