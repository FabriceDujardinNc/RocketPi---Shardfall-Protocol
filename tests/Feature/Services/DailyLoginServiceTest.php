<?php

use App\Models\Currency;
use App\Models\DailyLogin;
use App\Services\DailyLoginService;

it('records a login for today on first call', function () {
    $user = makeUser();
    $svc  = app(DailyLoginService::class);

    $login = $svc->recordToday($user);

    expect($login->streak_count)->toBe(1);
    expect($login->streak_day)->toBe(1);
    expect(DailyLogin::count())->toBe(1);
});

it('returns existing login on second call same day', function () {
    $user = makeUser();
    $svc  = app(DailyLoginService::class);

    $first  = $svc->recordToday($user);
    $second = $svc->recordToday($user);

    expect($second->id)->toBe($first->id);
    expect(DailyLogin::count())->toBe(1);
});

it('continues streak when user logged in yesterday', function () {
    $user = makeUser();

    DailyLogin::create([
        'user_id'      => $user->id,
        'login_date'   => now()->subDay()->toDateString(),
        'streak_day'   => 1,
        'streak_count' => 5,
    ]);

    $login = app(DailyLoginService::class)->recordToday($user);

    expect($login->streak_count)->toBe(6);
    expect($login->streak_day)->toBe(6);
});

it('resets streak when user skipped a day', function () {
    $user = makeUser();

    DailyLogin::create([
        'user_id'      => $user->id,
        'login_date'   => now()->subDays(3)->toDateString(),
        'streak_day'   => 5,
        'streak_count' => 10,
    ]);

    $login = app(DailyLoginService::class)->recordToday($user);

    expect($login->streak_count)->toBe(1);
    expect($login->streak_day)->toBe(1);
});

it('cycles streak_day from 30 back to 1 on day 31', function () {
    $user = makeUser();

    DailyLogin::create([
        'user_id'      => $user->id,
        'login_date'   => now()->subDay()->toDateString(),
        'streak_day'   => 30,
        'streak_count' => 30,
    ]);

    $login = app(DailyLoginService::class)->recordToday($user);

    expect($login->streak_count)->toBe(31);
    expect($login->streak_day)->toBe(1);
});

it('claims daily reward and credits currency', function () {
    $user = makeUser();
    $svc  = app(DailyLoginService::class);

    $result = $svc->claim($user);

    expect($result['streak_day'])->toBe(1);
    expect($result['reward'])->toEqualCanonicalizing(DailyLoginService::REWARDS_BY_DAY[1]);

    // Day 1 reward: 200 credits + 30 shards
    $credits = Currency::where('user_id', $user->id)->where('type', 'credits')->value('balance');
    $shards  = Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance');
    expect($credits)->toBe(200);
    expect($shards)->toBe(30);
});

it('refuses to claim twice on same day', function () {
    $user = makeUser();
    $svc  = app(DailyLoginService::class);

    $svc->claim($user);

    expect(fn () => $svc->claim($user))->toThrow(RuntimeException::class, 'déjà réclamée');
});

it('returns specific rewards for milestone days', function () {
    $svc = app(DailyLoginService::class);

    expect($svc->rewardForDay(1))->toBe(DailyLoginService::REWARDS_BY_DAY[1]);
    expect($svc->rewardForDay(7))->toBe(DailyLoginService::REWARDS_BY_DAY[7]);
    expect($svc->rewardForDay(15))->toBe(DailyLoginService::REWARDS_BY_DAY[15]);
    expect($svc->rewardForDay(30))->toBe(DailyLoginService::REWARDS_BY_DAY[30]);
    expect($svc->rewardForDay(2))->toBe(DailyLoginService::DEFAULT_REWARD);
});
