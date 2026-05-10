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

        // Lore débloqué progressivement par level d'affinité (5 paliers : 0/2/5/8/10)
        $loreUnlocks = [
            ['level' => 0,  'title' => 'Présentation',          'unlocked' => true,  'snippet' => $operator->lore],
            ['level' => 2,  'title' => 'Origines',              'unlocked' => $affinityLevel >= 2,  'snippet' => $affinityLevel >= 2 ? $this->lorePart($operator, 'origines') : null],
            ['level' => 5,  'title' => 'L\'incident Shardfall', 'unlocked' => $affinityLevel >= 5,  'snippet' => $affinityLevel >= 5 ? $this->lorePart($operator, 'shardfall') : null],
            ['level' => 8,  'title' => 'Vie privée',            'unlocked' => $affinityLevel >= 8,  'snippet' => $affinityLevel >= 8 ? $this->lorePart($operator, 'private') : null],
            ['level' => 10, 'title' => 'Confidence ultime',     'unlocked' => $affinityLevel >= 10, 'snippet' => $affinityLevel >= 10 ? $this->lorePart($operator, 'ultimate') : null],
        ];

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

    /**
     * Génère un fragment de lore à la volée selon le palier.
     * À terme : stocker dans operators.lore_unlocks JSON ou table dédiée.
     */
    private function lorePart(Operator $op, string $part): string
    {
        return match ($part) {
            'origines'   => "Avant le Shardfall, {$op->name} servait dans les rangs de la faction {$op->faction}. Spécialiste {$op->role}, son passé reste partiellement classifié.",
            'shardfall'  => "L'exposition aux Shards a transformé {$op->name}. Les changements physiques et mentaux observés défient encore la compréhension scientifique actuelle.",
            'private'    => "Loin des champs de bataille, {$op->name} cultive une passion pour des activités étonnamment ordinaires. Une humanité qui rappelle ce que l'on protège.",
            'ultimate'   => "La confidence ultime — révélée seulement aux commandants ayant gagné une affinité maximale avec {$op->name}.",
            default      => '',
        };
    }
}
