<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\PlayerOperator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $owned = PlayerOperator::with('operator:id,name,codename,faction,role,rarity,portrait_url,lore')
            ->where('user_id', $user->id)
            ->orderByDesc('obtained_at')
            ->get()
            ->map(fn (PlayerOperator $po) => [
                'id'              => $po->id,
                'duplicate_count' => $po->duplicate_count,
                'constellation'   => $po->constellation,
                'is_favorite'     => $po->is_favorite,
                'obtained_at'     => $po->obtained_at,
                'operator'        => $po->operator,
            ]);

        return Inertia::render('Player/Collection', [
            'operators' => $owned,
            'totalCount' => $owned->count(),
        ]);
    }
}
