<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOperatorRequest;
use App\Models\Operator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOperatorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Operator::class);

        $filters = $request->validate([
            'q'        => 'nullable|string|max:80',
            'faction'  => 'nullable|in:ORBIT,FERRO,VEIL',
            'rarity'   => 'nullable|in:common,rare,epic,legendary',
            'role'     => 'nullable|in:sniper,healer,scout,tank,explosives,assault,infiltrator,hacker',
            'trashed'  => 'nullable|in:with,only',
        ]);

        $query = Operator::query()
            ->when($filters['trashed'] ?? null, fn ($q, $t) => $t === 'only' ? $q->onlyTrashed() : $q->withTrashed())
            ->when($filters['q']       ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")
                ->orWhere('codename', 'like', "%{$v}%")))
            ->when($filters['faction'] ?? null, fn ($q, $v) => $q->where('faction', $v))
            ->when($filters['rarity']  ?? null, fn ($q, $v) => $q->where('rarity', $v))
            ->when($filters['role']    ?? null, fn ($q, $v) => $q->where('role', $v))
            ->orderBy('sort_order')
            ->orderBy('id');

        return Inertia::render('Admin/Operators/Index', [
            'operators' => $query->paginate(25)->withQueryString(),
            // Force-cast en objet pour qu'Inertia serialize `{}` plutôt que `[]`
            // côté JS — l'Index destructure filters.q et planterait sur un array.
            'filters'   => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Operator::class);

        return Inertia::render('Admin/Operators/Create', [
            'enums' => $this->enums(),
        ]);
    }

    public function store(StoreOperatorRequest $request): RedirectResponse
    {
        $operator = Operator::create($request->validated());

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('status', "Opérateur {$operator->name} créé.");
    }

    public function show(Operator $operator): Response
    {
        $this->authorize('view', $operator);

        return Inertia::render('Admin/Operators/Show', [
            'operator' => array_merge($operator->toArray(), [
                'lore_unlocks' => $operator->loreUnlocksWithDefaults(),
            ]),
        ]);
    }

    public function edit(Operator $operator): Response
    {
        $this->authorize('update', $operator);

        return Inertia::render('Admin/Operators/Edit', [
            'operator' => array_merge($operator->toArray(), [
                // On présente toujours les 5 paliers à l'éditeur, même s'ils ne sont pas
                // tous renseignés en BDD : ça évite la friction "ajouter palier 5 puis 8".
                'lore_unlocks' => $operator->loreUnlocksWithDefaults(),
            ]),
            'enums'    => $this->enums(),
        ]);
    }

    public function update(StoreOperatorRequest $request, Operator $operator): RedirectResponse
    {
        $operator->update($request->validated());

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('status', "Opérateur {$operator->name} mis à jour.");
    }

    public function destroy(Operator $operator): RedirectResponse
    {
        $this->authorize('delete', $operator);

        $operator->delete();

        return redirect()
            ->route('admin.operators.index')
            ->with('status', "Opérateur {$operator->name} archivé (soft delete).");
    }

    public function restore(int $id): RedirectResponse
    {
        $operator = Operator::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $operator);

        $operator->restore();

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('status', "Opérateur {$operator->name} restauré.");
    }

    private function enums(): array
    {
        return [
            'factions'      => Operator::FACTIONS,
            'roles'         => Operator::ROLES,
            'rarities'      => Operator::RARITIES,
            'ability_types' => ['active', 'passive', 'ultimate'],
        ];
    }
}
