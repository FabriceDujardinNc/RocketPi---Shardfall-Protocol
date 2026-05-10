<?php

use App\Models\Currency;
use App\Models\Mission;
use App\Models\MissionProgress;
use App\Services\LeaderboardService;
use App\Services\MissionService;

beforeEach(function () {
    // Mock LeaderboardService pour éviter Redis
    $mock = Mockery::mock(LeaderboardService::class);
    $mock->shouldReceive('activeSeasons')->andReturn(collect([]));
    $mock->shouldReceive('addPoints')->andReturn(0);
    $this->app->instance(LeaderboardService::class, $mock);
});

function makeMission(array $attrs = []): Mission
{
    return Mission::create(array_merge([
        'title'            => 'Test mission',
        'type'             => 'daily',
        'objective_type'   => 'pull',
        'objective_target' => 3,
        'rewards'          => [['type' => 'credits', 'amount' => 100]],
        'xp_reward'        => 50,
        'is_active'        => true,
    ], $attrs));
}

it('progresses missions matching objective_type', function () {
    $user    = makeUser();
    $pull    = makeMission(['objective_type' => 'pull', 'objective_target' => 5]);
    $login   = makeMission(['objective_type' => 'login', 'objective_target' => 1]);

    app(MissionService::class)->progressFor($user, 'pull', 2);

    $pp = MissionProgress::where('user_id', $user->id)->where('mission_id', $pull->id)->first();
    $lp = MissionProgress::where('user_id', $user->id)->where('mission_id', $login->id)->first();

    expect($pp->progress)->toBe(2);
    expect($pp->completed)->toBeFalse();
    expect($lp)->toBeNull();   // pas créée pour un autre objective_type
});

it('marks mission completed when target reached', function () {
    $user = makeUser();
    $m    = makeMission(['objective_target' => 3]);

    $newlyCompleted = app(MissionService::class)->progressFor($user, 'pull', 3);

    expect($newlyCompleted)->toBe(1);
    expect(MissionProgress::first())
        ->completed->toBeTrue()
        ->progress->toBe(3);
});

it('caps progress at objective_target (no overshoot)', function () {
    $user = makeUser();
    $m    = makeMission(['objective_target' => 5]);

    app(MissionService::class)->progressFor($user, 'pull', 10);

    expect(MissionProgress::first()->progress)->toBe(5);
});

it('does not re-complete already completed missions', function () {
    $user = makeUser();
    $m    = makeMission(['objective_target' => 3]);

    app(MissionService::class)->progressFor($user, 'pull', 3);
    $newlyCompleted = app(MissionService::class)->progressFor($user, 'pull', 3);

    expect($newlyCompleted)->toBe(0);
});

it('claims rewards and awards xp on completed mission', function () {
    $user = makeUser(['account_level' => 1, 'account_xp' => 0]);
    $m    = makeMission([
        'rewards'   => [['type' => 'shards', 'amount' => 50]],
        'xp_reward' => 75,
    ]);

    app(MissionService::class)->progressFor($user, 'pull', 3);
    $result = app(MissionService::class)->claim($user, $m);

    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))->toBe(50);
    expect($user->fresh()->account_xp)->toBe(75);
    expect(MissionProgress::first()->reward_claimed)->toBeTrue();
    expect($result['xp']['xp_gained'])->toBe(75);
});

it('refuses claim if mission not completed', function () {
    $user = makeUser();
    $m    = makeMission();

    app(MissionService::class)->progressFor($user, 'pull', 1);   // 1/3

    expect(fn () => app(MissionService::class)->claim($user, $m))
        ->toThrow(RuntimeException::class, 'non complétée');
});

it('refuses claim twice', function () {
    $user = makeUser();
    $m    = makeMission();

    app(MissionService::class)->progressFor($user, 'pull', 3);
    app(MissionService::class)->claim($user, $m);

    expect(fn () => app(MissionService::class)->claim($user, $m))
        ->toThrow(RuntimeException::class, 'déjà réclamée');
});

it('lists missions with progress hydrated', function () {
    $user = makeUser();
    makeMission(['title' => 'Daily 1', 'type' => 'daily', 'objective_target' => 2]);
    makeMission(['title' => 'Daily 2', 'type' => 'daily', 'objective_target' => 5]);
    makeMission(['title' => 'Weekly', 'type' => 'weekly']);

    app(MissionService::class)->progressFor($user, 'pull', 1);

    $list = app(MissionService::class)->listForUser($user, 'daily');

    expect($list)->toHaveCount(2);
    expect($list[0]['progress'])->toBe(1);
    expect($list[0]['completed'])->toBeFalse();
});
