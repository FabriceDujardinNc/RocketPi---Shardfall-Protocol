<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Faction;
use App\Models\PlayerOperator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $factions = Faction::query()
            ->withCount('operators')
            ->orderBy('slug')
            ->get();

        // Pour chaque faction, compter combien d'ops le joueur possède.
        $ownedCounts = PlayerOperator::query()
            ->where('user_id', $user->id)
            ->join('operators', 'player_operators.operator_id', '=', 'operators.id')
            ->selectRaw('operators.faction, count(*) as c')
            ->groupBy('operators.faction')
            ->pluck('c', 'faction');

        $factions = $factions->map(fn (Faction $f) => array_merge($f->toArray(), [
            'operators_owned' => (int) ($ownedCounts[$f->slug] ?? 0),
        ]));

        return Inertia::render('Player/Factions/Index', [
            'factions' => $factions,
        ]);
    }

    public function show(Request $request, Faction $faction): Response
    {
        $user = $request->user();

        $operators = $faction->operators()
            ->orderByDesc('rarity')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'codename', 'role', 'rarity', 'portrait_url', 'is_available']);

        $ownedIds = PlayerOperator::where('user_id', $user->id)
            ->whereIn('operator_id', $operators->pluck('id'))
            ->pluck('operator_id')
            ->all();

        $rows = $operators->map(fn ($op) => array_merge($op->toArray(), [
            'owned' => in_array($op->id, $ownedIds, true),
        ]));

        $byRarity = $rows->groupBy('rarity')->map->values()->toArray();

        return Inertia::render('Player/Factions/Show', [
            'faction'   => $faction,
            'byRarity'  => $byRarity,
            'stats'     => [
                'total' => $operators->count(),
                'owned' => count($ownedIds),
            ],
        ]);
    }
}
