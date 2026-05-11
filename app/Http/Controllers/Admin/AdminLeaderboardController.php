<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaderboardSeasonRequest;
use App\Models\LeaderboardEntry;
use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminLeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $service,
        private readonly RewardService $rewards,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', LeaderboardSeason::class);

        $seasons = LeaderboardSeason::orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->get();

        $rows = $seasons->map(fn (LeaderboardSeason $s) => [
            'id'                  => $s->id,
            'slug'                => $s->slug,
            'name'                => $s->name,
            'type'                => $s->type,
            'faction'             => $s->faction,
            'season_number'       => $s->season_number,
            'starts_at'           => $s->starts_at,
            'ends_at'             => $s->ends_at,
            'is_active'           => $s->is_active,
            'rewards_distributed' => $s->rewards_distributed,
            'participant_count'   => $s->is_active ? $this->service->participantCount($s) : LeaderboardEntry::where('season_id', $s->id)->count(),
            'is_expired'          => $s->ends_at?->isPast() ?? false,
        ]);

        return Inertia::render('Admin/Leaderboards/Index', [
            'seasons' => $rows,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LeaderboardSeason::class);
        return Inertia::render('Admin/Leaderboards/Create', $this->formDeps());
    }

    public function store(StoreLeaderboardSeasonRequest $request): RedirectResponse
    {
        $season = LeaderboardSeason::create($request->validated());
        return redirect()->route('admin.leaderboards.show', $season)
            ->with('status', "Saison « {$season->name} » créée.");
    }

    public function show(LeaderboardSeason $season): Response
    {
        $this->authorize('view', $season);

        if ($season->is_active) {
            $entries = $this->service->topN($season, 100);
            $source  = 'redis';
        } else {
            $entries = LeaderboardEntry::with('user:id,name,email,display_name,account_level')
                ->where('season_id', $season->id)
                ->orderBy('rank')
                ->limit(100)
                ->get()
                ->map(fn ($e) => [
                    'rank'          => $e->rank,
                    'user_id'       => $e->user_id,
                    'score'         => $e->score,
                    'name'          => $e->user?->name ?? 'Unknown',
                    'display_name'  => $e->user?->display_name,
                    'account_level' => $e->user?->account_level ?? 1,
                ])
                ->toArray();
            $source = 'mysql';
        }

        return Inertia::render('Admin/Leaderboards/Show', [
            'season' => $season->only([
                'id', 'slug', 'name', 'type', 'faction', 'season_number',
                'starts_at', 'ends_at', 'is_active', 'rewards_distributed',
            ]),
            'entries'           => $entries,
            'source'            => $source,
            'participantCount'  => $source === 'redis'
                ? $this->service->participantCount($season)
                : LeaderboardEntry::where('season_id', $season->id)->count(),
        ]);
    }

    public function edit(LeaderboardSeason $season): Response
    {
        $this->authorize('update', $season);
        return Inertia::render('Admin/Leaderboards/Edit', array_merge($this->formDeps(), [
            'season' => $season,
        ]));
    }

    public function update(StoreLeaderboardSeasonRequest $request, LeaderboardSeason $season): RedirectResponse
    {
        $season->update($request->validated());
        return redirect()->route('admin.leaderboards.show', $season)
            ->with('status', "Saison « {$season->name} » mise à jour.");
    }

    public function destroy(LeaderboardSeason $season): RedirectResponse
    {
        $this->authorize('delete', $season);
        $season->delete();
        return redirect()->route('admin.leaderboards.index')
            ->with('status', 'Saison supprimée. Les entries archivées MySQL sont cascade-deleted.');
    }

    public function reset(LeaderboardSeason $season): RedirectResponse
    {
        $this->authorize('update', $season);

        if (! $season->is_active) {
            return back()->withErrors(['season' => 'Saison déjà fermée.']);
        }

        $archived    = $this->service->snapshotToMysql($season);
        $distributed = $this->service->distributeRewards($season, $this->rewards);

        return back()->with('status', "Saison {$season->name} fermée — {$archived} entries archivées, {$distributed} récompenses distribuées.");
    }

    private function formDeps(): array
    {
        return [
            'enums' => [
                'types'    => ['weekly', 'monthly', 'seasonal', 'annual', 'collection', 'faction'],
                'factions' => ['ORBIT', 'FERRO', 'VEIL'],
            ],
        ];
    }
}
