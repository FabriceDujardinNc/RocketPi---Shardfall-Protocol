<?php

use App\Models\LeaderboardEntry;
use App\Models\LeaderboardSeason;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

afterAll(function () {
    Redis::flushdb();
});

it('shows empty state when no annual season exists', function () {
    $u = makeUser();
    $this->actingAs($u)
        ->get('/hall-of-fame')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/HallOfFame')
            ->where('totalSeasons', 0)
            ->has('palmares', 0)
        );
});

it('lists archived annual seasons with top entries from MySQL snapshot', function () {
    $u = makeUser();
    $season = LeaderboardSeason::create([
        'name' => 'Annuel 2087', 'type' => 'annual', 'season_number' => 1,
        'starts_at' => now()->subYear(), 'ends_at' => now()->subMonth(),
        'is_active' => false,
    ]);

    $winner = makeUser(['display_name' => 'Vex', 'account_level' => 99]);
    LeaderboardEntry::create(['season_id' => $season->id, 'user_id' => $winner->id, 'rank' => 1, 'score' => 99999]);

    $this->actingAs($u)
        ->get('/hall-of-fame')
        ->assertInertia(fn ($p) => $p
            ->where('totalSeasons', 1)
            ->has('palmares', 1)
            ->where('palmares.0.is_active', false)
            ->where('palmares.0.entries.0.display_name', 'Vex')
            ->where('palmares.0.entries.0.rank', 1)
            ->where('palmares.0.entries.0.score', 99999)
        );
});

it('shows current annual season top from Redis ZSET', function () {
    $u = makeUser();
    $season = LeaderboardSeason::create([
        'name' => 'Annuel 2088', 'type' => 'annual', 'season_number' => 2,
        'starts_at' => now()->subMonth(), 'ends_at' => now()->addYear(),
        'is_active' => true,
    ]);
    $svc = app(LeaderboardService::class);
    $top1 = makeUser(['display_name' => 'ApexHunter']);
    $svc->addPoints($top1, $season, 5000);

    $this->actingAs($u)
        ->get('/hall-of-fame')
        ->assertInertia(fn ($p) => $p
            ->where('palmares.0.is_active', true)
            ->where('palmares.0.entries.0.display_name', 'ApexHunter')
            ->where('palmares.0.entries.0.score', 5000)
        );
});

it('orders palmares from newest to oldest by season_number', function () {
    $u = makeUser();
    LeaderboardSeason::create([
        'name' => 'Annuel 2087', 'type' => 'annual', 'season_number' => 1,
        'starts_at' => now()->subYears(2), 'ends_at' => now()->subYear(),
        'is_active' => false,
    ]);
    LeaderboardSeason::create([
        'name' => 'Annuel 2088', 'type' => 'annual', 'season_number' => 2,
        'starts_at' => now()->subYear(), 'ends_at' => now()->addMonth(),
        'is_active' => true,
    ]);

    $this->actingAs($u)
        ->get('/hall-of-fame')
        ->assertInertia(fn ($p) => $p
            ->where('palmares.0.season_number', 2)
            ->where('palmares.1.season_number', 1)
        );
});

it('requires auth', function () {
    $this->get('/hall-of-fame')->assertRedirect('/login');
});
