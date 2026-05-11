<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
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

    /**
     * Historique des saisons closes — lit `leaderboard_entries` (snapshot MySQL
     * écrit par `LeaderboardService::snapshotToMysql` au reset de saison).
     *
     * On affiche les saisons archivées (non `is_active`) avec le rang et score
     * que le joueur y a obtenu, ses 3 meilleurs résultats, et un récap stats.
     */
    public function history(Request $request): Response
    {
        $user = $request->user();

        $entries = LeaderboardEntry::with('season:id,name,type,faction,season_number,starts_at,ends_at')
            ->where('user_id', $user->id)
            ->whereHas('season', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('id')
            ->get();

        $rows = $entries->map(fn (LeaderboardEntry $e) => [
            'season_id'    => $e->season_id,
            'season_name'  => $e->season?->name,
            'season_type'  => $e->season?->type,
            'faction'      => $e->season?->faction,
            'starts_at'    => $e->season?->starts_at,
            'ends_at'      => $e->season?->ends_at,
            'rank'         => $e->rank,
            'score'        => $e->score,
            'games_played' => $e->games_played,
            'wins'         => $e->wins,
        ])->values();

        $best = $rows->whereNotNull('rank')->sortBy('rank')->take(3)->values();

        return Inertia::render('Player/LeaderboardHistory', [
            'history' => $rows,
            'best'    => $best,
            'totals'  => [
                'archived_seasons' => $rows->count(),
                'top_1_count'      => $rows->where('rank', 1)->count(),
                'top_10_count'     => $rows->whereBetween('rank', [1, 10])->count(),
            ],
        ]);
    }
}
