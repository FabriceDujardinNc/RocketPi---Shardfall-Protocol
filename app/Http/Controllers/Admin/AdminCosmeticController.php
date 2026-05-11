<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCosmeticRequest;
use App\Models\Cosmetic;
use App\Models\Operator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCosmeticController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Cosmetic::class);

        $filters = $request->validate([
            'q'           => 'nullable|string|max:80',
            'type'        => 'nullable|in:skin,title,voiceline,banner,border',
            'rarity'      => 'nullable|in:common,rare,epic,legendary',
            'operator_id' => 'nullable|integer',
        ]);

        $cosmetics = Cosmetic::query()
            ->with('operator:id,name,codename,faction')
            ->withCount('unlockedBy')
            ->when($filters['q']           ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")->orWhere('slug', 'like', "%{$v}%")))
            ->when($filters['type']        ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['rarity']      ?? null, fn ($q, $v) => $q->where('rarity', $v))
            ->when($filters['operator_id'] ?? null, fn ($q, $v) => $q->where('operator_id', $v))
            ->orderBy('type')
            ->orderByDesc('rarity')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Cosmetics/Index', [
            'cosmetics' => $cosmetics,
            'filters'   => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Cosmetic::class);
        return Inertia::render('Admin/Cosmetics/Create', $this->formDeps());
    }

    public function store(StoreCosmeticRequest $request): RedirectResponse
    {
        $c = Cosmetic::create($request->validated());
        return redirect()->route('admin.cosmetics.edit', $c)
            ->with('status', "Cosmétique « {$c->name} » créé.");
    }

    public function edit(Cosmetic $cosmetic): Response
    {
        $this->authorize('update', $cosmetic);
        return Inertia::render('Admin/Cosmetics/Edit', array_merge($this->formDeps(), [
            'cosmetic' => $cosmetic->load('operator:id,name,codename'),
        ]));
    }

    public function update(StoreCosmeticRequest $request, Cosmetic $cosmetic): RedirectResponse
    {
        $cosmetic->update($request->validated());
        return redirect()->route('admin.cosmetics.edit', $cosmetic)
            ->with('status', "Cosmétique « {$cosmetic->name} » mis à jour.");
    }

    public function destroy(Cosmetic $cosmetic): RedirectResponse
    {
        $this->authorize('delete', $cosmetic);
        $cosmetic->delete();
        return redirect()->route('admin.cosmetics.index')
            ->with('status', 'Cosmétique supprimé. Les déblocages joueurs ont été cascade-deleted.');
    }

    private function formDeps(): array
    {
        return [
            'enums' => [
                'types'    => Cosmetic::TYPES,
                'rarities' => Cosmetic::RARITIES,
            ],
            'operators' => Operator::orderBy('name')->get(['id', 'name', 'codename', 'faction']),
        ];
    }
}
