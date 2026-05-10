<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $service) {}

    public function index(Request $request): Response
    {
        $seasons = $this->service->activeSeasons();
        $current = $seasons->first();

        return $current
            ? $this->renderSeason($request, $current, $seasons)
            : $this->renderEmpty();
    }

    public function show(Request $request, LeaderboardSeason $season): Response
    {
        abort_unless($season->is_active, 404);
        return $this->renderSeason($request, $season, $this->service->activeSeasons());
    }

    private function renderSeason(Request $request, LeaderboardSeason $season, $allSeasons): Response
    {
        $user = $request->user();

        return Inertia::render('Player/Leaderboard', [
            'currentSeason'    => $season->only([
                'id', 'name', 'type', 'faction', 'season_number', 'starts_at', 'ends_at',
            ]),
            'seasons' => $allSeasons->map(fn ($s) => [
                'id'      => $s->id,
                'name'    => $s->name,
                'type'    => $s->type,
                'faction' => $s->faction,
            ])->values(),
            'topEntries'        => $this->service->topN($season, 100),
            'participantCount'  => $this->service->participantCount($season),
            'userRank'          => $this->service->rankOf($user, $season),
            'userScore'         => $this->service->scoreOf($user, $season),
            'neighbors'         => $this->service->neighborsOf($user, $season, 3),
        ]);
    }

    private function renderEmpty(): Response
    {
        return Inertia::render('Player/Leaderboard', [
            'currentSeason'    => null,
            'seasons'          => [],
            'topEntries'       => [],
            'participantCount' => 0,
            'userRank'         => null,
            'userScore'        => 0,
            'neighbors'        => [],
        ]);
    }
}
