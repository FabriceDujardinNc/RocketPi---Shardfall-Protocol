<?php

use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

afterAll(function () {
    Redis::flushdb();
});

it('serves /top without auth and renders empty state if no season', function () {
    $this->get('/top')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Public/Leaderboard')
            ->where('season', null)
            ->where('participantCount', 0)
            ->has('entries', 0)
        );
});

it('serves /top with active weekly season and top 100 entries', function () {
    $season = LeaderboardSeason::create([
        'name' => 'Hebdo S1', 'type' => 'weekly', 'season_number' => 1,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(),
        'is_active' => true,
    ]);

    $svc = app(LeaderboardService::class);
    $u1 = makeUser(['display_name' => 'ApexHunter']);
    $u2 = makeUser(['display_name' => 'OrbitMancer']);
    $svc->addPoints($u1, $season, 200);
    $svc->addPoints($u2, $season, 100);

    $this->get('/top')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Public/Leaderboard')
            ->where('season.name', 'Hebdo S1')
            ->where('participantCount', 2)
            ->has('entries', 2)
            ->where('entries.0.rank', 1)
            ->where('entries.0.display_name', 'ApexHunter')
            ->where('entries.0.score', 200)
        );
});

it('does not expose PII (no email or user_id) in /top response', function () {
    $season = LeaderboardSeason::create([
        'name' => 'Hebdo S2', 'type' => 'weekly', 'season_number' => 2,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(),
        'is_active' => true,
    ]);
    $svc = app(LeaderboardService::class);
    $u = makeUser(['email' => 'secret@example.com', 'display_name' => 'Anon']);
    $svc->addPoints($u, $season, 50);

    $body = $this->get('/top')->getContent();
    expect($body)->not->toContain('secret@example.com');
    expect($body)->not->toContain('"user_id"');
});
