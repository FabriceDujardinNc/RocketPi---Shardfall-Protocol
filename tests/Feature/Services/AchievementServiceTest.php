<?php

use App\Models\Achievement;
use App\Models\Currency;
use App\Models\UserAchievement;
use App\Services\AchievementService;

function makeAchievement(array $attrs = []): Achievement
{
    return Achievement::create(array_merge([
        'key'         => 'test_'.uniqid(),
        'title'       => 'Test achievement',
        'description' => 'desc',
        'category'    => 'collection',
        'rewards'     => [['type' => 'shards', 'amount' => 100]],
        'is_hidden'   => false,
    ], $attrs));
}

it('returns null when tracking unknown key', function () {
    $user = makeUser();

    $result = app(AchievementService::class)->track($user, 'nonexistent_key');

    expect($result)->toBeNull();
    expect(UserAchievement::count())->toBe(0);
});

it('creates progress row on first track', function () {
    $user = makeUser();
    makeAchievement(['key' => 'first_pull']);

    app(AchievementService::class)->track($user, 'first_pull');

    expect(UserAchievement::count())->toBe(1);
    expect(UserAchievement::first())
        ->progress->toBe(1)
        ->completed->toBeTrue();   // target=1 par défaut
});

it('marks completed when progressive target reached', function () {
    $user = makeUser();
    makeAchievement(['key' => 'pulled_10']);

    app(AchievementService::class)->track($user, 'pulled_10', 5, 10);
    expect(UserAchievement::first())
        ->progress->toBe(5)
        ->completed->toBeFalse();

    app(AchievementService::class)->track($user, 'pulled_10', 5, 10);
    expect(UserAchievement::first())
        ->progress->toBe(10)
        ->completed->toBeTrue();
});

it('does not increment after completion', function () {
    $user = makeUser();
    makeAchievement(['key' => 'pulled_10']);

    app(AchievementService::class)->track($user, 'pulled_10', 10, 10);
    app(AchievementService::class)->track($user, 'pulled_10', 99, 10);

    expect(UserAchievement::first()->progress)->toBe(10);
});

it('claims rewards on completed achievement', function () {
    $user = makeUser();
    makeAchievement(['key' => 'first_pull', 'rewards' => [['type' => 'shards', 'amount' => 100]]]);

    $svc = app(AchievementService::class);
    $progress = $svc->track($user, 'first_pull');
    $svc->claim($user, $progress);

    expect(Currency::where('user_id', $user->id)->where('type', 'shards')->value('balance'))->toBe(100);
    expect($progress->fresh()->reward_claimed)->toBeTrue();
});

it('refuses claim if not completed', function () {
    $user = makeUser();
    makeAchievement(['key' => 'pulled_10']);

    $svc = app(AchievementService::class);
    $progress = $svc->track($user, 'pulled_10', 1, 10);

    expect(fn () => $svc->claim($user, $progress))
        ->toThrow(RuntimeException::class, 'non complété');
});

it('refuses claim twice', function () {
    $user = makeUser();
    makeAchievement(['key' => 'first_pull']);

    $svc = app(AchievementService::class);
    $progress = $svc->track($user, 'first_pull');
    $svc->claim($user, $progress);

    expect(fn () => $svc->claim($user, $progress->fresh()))
        ->toThrow(RuntimeException::class, 'déjà réclamée');
});

it('hides hidden non-unlocked achievements in listForUser', function () {
    $user = makeUser();
    makeAchievement(['key' => 'visible_one']);
    $hidden = makeAchievement(['key' => 'secret_one', 'is_hidden' => true]);

    $list = app(AchievementService::class)->listForUser($user);
    expect($list)->toHaveCount(1);

    // Une fois unlock l'achievement caché, il apparaît
    app(AchievementService::class)->track($user, 'secret_one');
    $list = app(AchievementService::class)->listForUser($user);
    expect($list)->toHaveCount(2);
});
