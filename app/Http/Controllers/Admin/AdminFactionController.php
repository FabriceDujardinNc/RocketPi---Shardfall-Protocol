<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFactionRequest;
use App\Models\Faction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminFactionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Faction::class);

        return Inertia::render('Admin/Factions/Index', [
            'factions' => Faction::withCount('operators')->orderBy('slug')->get(),
        ]);
    }

    public function show(Faction $faction): Response
    {
        $this->authorize('view', $faction);

        // Page collection : tous les opérateurs de la faction.
        $operators = $faction->operators()
            ->orderBy('rarity', 'desc')   // legendary → common
            ->orderBy('name')
            ->get(['id', 'name', 'codename', 'role', 'rarity', 'is_available', 'is_rate_up', 'portrait_url']);

        $byRarity = $operators->groupBy('rarity')->map->values()->toArray();

        return Inertia::render('Admin/Factions/Show', [
            'faction'    => $faction,
            'operators'  => $operators,
            'byRarity'   => $byRarity,
            'stats'      => [
                'total'       => $operators->count(),
                'rate_up'     => $operators->where('is_rate_up', true)->count(),
                'unavailable' => $operators->where('is_available', false)->count(),
            ],
        ]);
    }

    public function edit(Faction $faction): Response
    {
        $this->authorize('update', $faction);
        return Inertia::render('Admin/Factions/Edit', [
            'faction' => $faction,
        ]);
    }

    public function update(UpdateFactionRequest $request, Faction $faction): RedirectResponse
    {
        $faction->update($request->validated());
        return redirect()->route('admin.factions.show', $faction)
            ->with('status', "Faction {$faction->slug} mise à jour.");
    }
}
