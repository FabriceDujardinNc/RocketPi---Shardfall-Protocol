<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\OperatorAffinity;
use App\Models\PlayerOperator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $affinities = OperatorAffinity::where('user_id', $user->id)
            ->get()
            ->keyBy('operator_id');

        $owned = PlayerOperator::with('operator:id,slug,name,codename,faction,role,rarity,portrait_url,lore')
            ->where('user_id', $user->id)
            ->orderByDesc('obtained_at')
            ->get()
            ->map(function (PlayerOperator $po) use ($affinities) {
                $aff = $affinities->get($po->operator_id);
                return [
                    'id'              => $po->id,
                    'duplicate_count' => $po->duplicate_count,
                    'constellation'   => $po->constellation,
                    'is_favorite'     => $po->is_favorite,
                    'obtained_at'     => $po->obtained_at,
                    'operator'        => $po->operator,
                    'affinity'        => $aff ? [
                        'level'      => $aff->level,
                        'xp_current' => $aff->xp_current,
                        'next_xp'    => 100 * max(1, $aff->level + 1),
                    ] : ['level' => 0, 'xp_current' => 0, 'next_xp' => 100],
                ];
            });

        return Inertia::render('Player/Collection', [
            'operators' => $owned,
            'totalCount' => $owned->count(),
        ]);
    }
}
