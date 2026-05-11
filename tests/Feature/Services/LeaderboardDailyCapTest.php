<?php

use App\Models\LeaderboardSeason;
use App\Models\Setting;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

afterAll(function () {
    Redis::flushdb();
});

function capSeason(): LeaderboardSeason
{
    return LeaderboardSeason::create([
        'name'          => 'CapTest',
        'type'          => 'weekly',
        'season_number' => 999,
        'starts_at'     => now()->subDay(),
        'ends_at'       => now()->addWeek(),
        'is_active'     => true,
    ]);
}

it('clips points to remaining daily cap', function () {
    Setting::put('leaderboard.daily_cap', 1000, 'int');
    $u  = makeUser();
    $s  = capSeason();
    $svc = app(LeaderboardService::class);

    expect($svc->addPoints($u, $s, 600))->toBe(600);
    // Le second appel ne peut ajouter que 400 (1000 - 600)
    expect($svc->addPoints($u, $s, 600))->toBe(1000);
    expect($svc->dailyEarned($u, $s))->toBe(1000);
});

it('rejects further points once daily cap is reached', function () {
    Setting::put('leaderboard.daily_cap', 200, 'int');
    $u  = makeUser();
    $s  = capSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints($u, $s, 200);
    $svc->addPoints($u, $s, 100);

    expect($svc->scoreOf($u, $s))->toBe(200);
    expect($svc->dailyEarned($u, $s))->toBe(200);
});

it('cap is independent per user and per season', function () {
    Setting::put('leaderboard.daily_cap', 100, 'int');
    $a = makeUser();
    $b = makeUser();
    $s1 = capSeason();
    $s2 = capSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints($a, $s1, 100);
    $svc->addPoints($a, $s2, 100); // autre saison
    $svc->addPoints($b, $s1, 100); // autre user

    expect($svc->scoreOf($a, $s1))->toBe(100);
    expect($svc->scoreOf($a, $s2))->toBe(100);
    expect($svc->scoreOf($b, $s1))->toBe(100);
});

it('cap 0 disables the limit', function () {
    Setting::put('leaderboard.daily_cap', 0, 'int');
    $u  = makeUser();
    $s  = capSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints($u, $s, 50000);
    $svc->addPoints($u, $s, 50000);
    expect($svc->scoreOf($u, $s))->toBe(100000);
});

it('cap defaults to 5000 when setting is absent', function () {
    Setting::where('key', 'leaderboard.daily_cap')->delete();
    \Illuminate\Support\Facades\Cache::forget('app:settings:all');
    $u  = makeUser();
    $s  = capSeason();
    $svc = app(LeaderboardService::class);

    $svc->addPoints($u, $s, 4000);
    $svc->addPoints($u, $s, 4000);

    expect($svc->scoreOf($u, $s))->toBe(5000);
});
