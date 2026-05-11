<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Operator;
use App\Models\OperatorAffinity;
use App\Models\PlayerOperator;
use App\Services\AffinityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperatorController extends Controller
{
    public function show(Request $request, Operator $operator): Response
    {
        $user = $request->user();

        $playerOp = PlayerOperator::where('user_id', $user->id)
            ->where('operator_id', $operator->id)
            ->first();

        $affinity = OperatorAffinity::where('user_id', $user->id)
            ->where('operator_id', $operator->id)
            ->first();

        $affinityLevel = $affinity?->level ?? 0;

        // Lit les paliers de lore depuis operators.lore_unlocks (JSON).
        // Le snippet n'est exposé au client que si le joueur a atteint le palier ;
        // ça évite de leaker le contenu via les devtools.
        $loreUnlocks = collect($operator->loreUnlocksWithDefaults())
            ->map(fn ($u) => [
                'level'    => $u['level'],
                'title'    => $u['title'],
                'unlocked' => $affinityLevel >= $u['level'],
                'snippet'  => $affinityLevel >= $u['level'] ? $u['snippet'] : null,
            ])
            ->all();

        $fragmentsBalance = (int) (Currency::where('user_id', $user->id)
            ->where('type', 'fragments_'.$operator->codename)
            ->value('balance') ?? 0);

        return Inertia::render('Player/OperatorDetail', [
            'operator' => $operator->only([
                'id', 'name', 'codename', 'faction', 'role', 'rarity',
                'lore', 'portrait_url',
                'stat_hp', 'stat_damage', 'stat_mobility',
                'weapon_name', 'weapon_description', 'abilities',
            ]),
            'owned'    => $playerOp !== null,
            'duplicateCount' => $playerOp?->duplicate_count ?? 0,
            'constellation' => $playerOp?->constellation ?? 0,
            'affinity' => [
                'level'      => $affinityLevel,
                'xp_current' => $affinity?->xp_current ?? 0,
                'next_xp'    => app(AffinityService::class)->thresholdFor($affinityLevel),
                'is_max'     => $affinityLevel >= AffinityService::MAX_LEVEL,
            ],
            'loreUnlocks' => $loreUnlocks,
            'fragmentsBalance' => $fragmentsBalance,
        ]);
    }
}
