<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\BattlePass;
use App\Models\BattlePassTier;
use App\Models\Currency;
use App\Services\BattlePassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BattlePassController extends Controller
{
    public function __construct(private readonly BattlePassService $service) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $bp   = $this->service->activeBattlePass();

        if (! $bp) {
            return Inertia::render('Player/BattlePass', [
                'season'   => null,
                'tiers'    => [],
                'progress' => null,
                'shards'   => 0,
            ]);
        }

        $progress = $this->service->progressFor($user, $bp);
        $shards   = (int) (Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_SHARDS)
            ->value('balance') ?? 0);

        return Inertia::render('Player/BattlePass', [
            'season' => [
                'id'             => $bp->id,
                'name'           => $bp->name,
                'season_number'  => $bp->season_number,
                'total_tiers'    => $bp->total_tiers,
                'starts_at'      => $bp->starts_at,
                'ends_at'        => $bp->ends_at,
                'premium_price_shards' => $bp->premium_price_shards,
            ],
            'tiers'    => $bp->tiers->map(fn (BattlePassTier $t) => [
                'id'             => $t->id,
                'tier_number'    => $t->tier_number,
                'xp_required'    => $t->xp_required,
                'free_reward'    => $t->free_reward,
                'premium_reward' => $t->premium_reward,
                'is_milestone'   => $t->is_milestone,
            ]),
            'progress' => [
                'is_premium'    => $progress->is_premium,
                'xp_earned'     => $progress->xp_earned,
                'current_tier'  => $progress->current_tier,
                'claimed_tiers' => $progress->claimed_tiers ?? [],
            ],
            'shards' => $shards,
        ]);
    }

    public function purchase(Request $request, BattlePass $battlePass): RedirectResponse
    {
        try {
            $this->service->purchase($request->user(), $battlePass, $request->ip());
            return back()->with('status', 'Battle Pass premium activé !');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['battlepass' => $e->getMessage()]);
        }
    }

    public function claim(Request $request, BattlePassTier $tier): RedirectResponse
    {
        try {
            $result = $this->service->claim($request->user(), $tier, $request->ip());
            $msg = "Palier {$result['tier_number']} réclamé.";
            return back()->with('status', $msg);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['battlepass' => $e->getMessage()]);
        }
    }
}
