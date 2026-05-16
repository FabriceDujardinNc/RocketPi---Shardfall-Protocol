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
                'id', 'slug', 'name', 'type', 'faction', 'season_number', 'starts_at', 'ends_at',
            ]),
            'seasons' => $allSeasons->map(fn ($s) => [
                'id'      => $s->id,
                'slug'    => $s->slug,
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
     * Hall of Fame — palmarès annuel.
     *
     * Liste les saisons `type=annual` (terminées ou en cours), avec le top 100
     * de chacune (LeaderboardEntry pour les archivées, Redis ZSET pour les
     * en cours). Page d'honneur — pas de claim, pas de stats personnelles.
     */
    public function hallOfFame(): Response
    {
        $seasons = LeaderboardSeason::query()
            ->where('type', 'annual')
            ->orderByDesc('season_number')
            ->get();

        $palmares = $seasons->map(function (LeaderboardSeason $s) {
            $isArchived = ! $s->is_active;

            if ($isArchived) {
                // Saison close — lit le snapshot MySQL
                $entries = LeaderboardEntry::where('season_id', $s->id)
                    ->with('user:id,display_name,name,slug,account_level')
                    ->orderBy('rank')
                    ->limit(100)
                    ->get()
                    ->map(fn ($e) => [
                        'rank'         => $e->rank,
                        'score'        => $e->score,
                        'display_name' => $e->user?->display_name ?? $e->user?->name ?? 'Unknown',
                        'slug'         => $e->user?->slug,
                        'account_level' => $e->user?->account_level ?? 1,
                    ]);
            } else {
                // Saison en cours — top 100 Redis (réutilise le service)
                $entries = collect($this->service->topN($s, 100))
                    ->map(fn ($e) => [
                        'rank'         => $e['rank'],
                        'score'        => $e['score'],
                        'display_name' => $e['display_name'] ?? $e['name'],
                        'slug'         => null,
                        'account_level' => $e['account_level'] ?? 1,
                    ]);
            }

            return [
                'id'           => $s->id,
                'name'         => $s->name,
                'season_number' => $s->season_number,
                'starts_at'    => $s->starts_at,
                'ends_at'      => $s->ends_at,
                'is_active'    => $isArchived ? false : true,
                'entries'      => $entries,
            ];
        });

        return Inertia::render('Player/HallOfFame', [
            'palmares' => $palmares,
            'totalSeasons' => $seasons->count(),
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
