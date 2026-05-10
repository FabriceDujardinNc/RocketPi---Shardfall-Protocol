<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\GachaPull;
use App\Models\PityCounter;
use App\Services\GachaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GachaApiController extends Controller
{
    public function __construct(private readonly GachaService $service) {}

    public function pull(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'banner_id' => 'required|integer|exists:banners,id',
            'count'     => 'required|integer|in:1,10',
        ]);

        $banner = Banner::find($validated['banner_id']);

        try {
            $results = $this->service->pull(
                user: $request->user(),
                banner: $banner,
                count: $validated['count'],
                sessionId: $request->session()?->getId(),
                ipAddress: $request->ip(),
            );

            return response()->json([
                'results' => array_map(fn ($r) => [
                    'operator' => $r['operator']->only(['id', 'codename', 'name', 'rarity', 'faction']),
                    'is_new'   => $r['is_new'],
                ], $results),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function pityStatus(Request $request, Banner $banner): JsonResponse
    {
        $pity = PityCounter::firstOrNew(
            ['user_id' => $request->user()->id, 'banner_id' => $banner->id],
            ['legendary_counter' => 0, 'epic_counter' => 0, 'total_pulls' => 0]
        );

        return response()->json([
            'legendary_counter' => $pity->legendary_counter,
            'epic_counter'      => $pity->epic_counter,
            'total_pulls'       => $pity->total_pulls,
            'pity_legendary'    => $banner->pity_legendary,
            'pity_epic'         => $banner->pity_epic,
            'soft_pity_start'   => $banner->soft_pity_start,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $logs = GachaPull::with('operator:id,codename,name,rarity', 'banner:id,name')
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->paginate(50);

        return response()->json($logs);
    }
}
