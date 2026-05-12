<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Currency;
use App\Models\GachaPull;
use App\Models\PityCounter;
use App\Services\GachaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GachaController extends Controller
{
    public function __construct(private readonly GachaService $gacha) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $banners = Banner::where('is_active', true)
            ->orderByDesc('type')
            ->get([
                'id', 'slug', 'name', 'tag', 'subtitle', 'type', 'featured_operator',
                'rate_up_operators', 'banner_image_url',
                'rate_legendary', 'rate_epic', 'rate_rare', 'rate_common',
                'pity_legendary', 'soft_pity_start', 'pity_epic',
                'starts_at', 'ends_at',
            ]);

        $shards = Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_SHARDS)
            ->value('balance') ?? 0;

        return Inertia::render('Player/Gacha', [
            'banners' => $banners,
            'shards'  => (int) $shards,
        ]);
    }

    public function show(Request $request, Banner $banner): Response
    {
        $user = $request->user();
        abort_unless($banner->is_active, 404);

        $banner->load([]);

        $pity = PityCounter::firstOrNew(
            ['user_id' => $user->id, 'banner_id' => $banner->id],
            ['legendary_counter' => 0, 'epic_counter' => 0, 'total_pulls' => 0]
        );

        $shards = Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_SHARDS)
            ->value('balance') ?? 0;

        $recentPulls = GachaPull::with('operator:id,name,codename,rarity,faction,portrait_url')
            ->where('user_id', $user->id)
            ->where('banner_id', $banner->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('Player/GachaBanner', [
            'banner' => $banner,
            'pity'   => [
                'legendary_counter' => $pity->legendary_counter,
                'epic_counter'      => $pity->epic_counter,
                'total_pulls'       => $pity->total_pulls,
            ],
            'shards' => (int) $shards,
            'cost'   => [
                'single' => GachaService::COST_PER_PULL,
                'ten'    => GachaService::COST_PER_TEN,
            ],
            'recentPulls' => $recentPulls,
        ]);
    }

    public function pull(Request $request, Banner $banner): RedirectResponse
    {
        $count = (int) $request->input('count', 1);
        $count = $count === 10 ? 10 : 1;

        try {
            $results = $this->gacha->pull(
                user: $request->user(),
                banner: $banner,
                count: $count,
                sessionId: $request->session()->getId(),
                ipAddress: $request->ip(),
            );

            $payload = array_map(fn ($r) => [
                'operator' => [
                    'id'           => $r['operator']->id,
                    'name'         => $r['operator']->name,
                    'codename'     => $r['operator']->codename,
                    'rarity'       => $r['operator']->rarity,
                    'faction'      => $r['operator']->faction,
                    'portrait_url' => $r['operator']->portrait_url,
                ],
                'is_new'        => $r['is_new'],
                'was_pity_hit'  => $r['pull']->was_pity_hit,
                'was_soft_pity' => $r['pull']->was_soft_pity,
                'was_rate_up'   => $r['pull']->was_rate_up,
            ], $results);

            return back()->with([
                'pullResults' => $payload,
                'status' => "Tirage ×{$count} effectué.",
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['gacha' => $e->getMessage()]);
        }
    }
}
