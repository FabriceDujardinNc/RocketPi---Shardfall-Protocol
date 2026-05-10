<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardApiController extends Controller
{
    public function __construct(private readonly LeaderboardService $service) {}

    public function top100(LeaderboardSeason $season): JsonResponse
    {
        return response()->json([
            'entries' => $this->service->topN($season, 100),
            'participantCount' => $this->service->participantCount($season),
        ]);
    }

    public function playerRank(Request $request, LeaderboardSeason $season): JsonResponse
    {
        return response()->json([
            'rank'  => $this->service->rankOf($request->user(), $season),
            'score' => $this->service->scoreOf($request->user(), $season),
        ]);
    }

    public function around(Request $request, LeaderboardSeason $season): JsonResponse
    {
        return response()->json([
            'neighbors' => $this->service->neighborsOf($request->user(), $season, 3),
        ]);
    }
}
