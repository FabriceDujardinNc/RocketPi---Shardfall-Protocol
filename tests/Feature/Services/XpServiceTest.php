<?php

use App\Services\XpService;

it('awards xp without level up if below threshold', function () {
    $user = makeUser(['account_level' => 5, 'account_xp' => 100]);
    $svc  = app(XpService::class);

    $result = $svc->award($user, 50);

    expect($user->fresh())
        ->account_level->toBe(5)
        ->account_xp->toBe(150);
    expect($result)
        ->xp_gained->toBe(50)
        ->leveled_up->toBeFalse()
        ->new_level->toBe(5);
});

it('levels up when xp reaches threshold', function () {
    // Niveau 1 → 2 = 100 XP requis
    $user = makeUser(['account_level' => 1, 'account_xp' => 50]);
    $svc  = app(XpService::class);

    $svc->award($user, 75);

    expect($user->fresh())
        ->account_level->toBe(2)   // 50 + 75 = 125 → niveau up, 25 reste
        ->account_xp->toBe(25);
});

it('cascades multiple level ups in one award', function () {
    // 100 + 200 + 300 = 600 XP → 1 → 4
    $user = makeUser(['account_level' => 1, 'account_xp' => 0]);
    $svc  = app(XpService::class);

    $result = $svc->award($user, 600);

    expect($user->fresh())
        ->account_level->toBe(4)
        ->account_xp->toBe(0);
    expect($result['leveled_up'])->toBeTrue();
    expect($result['new_level'])->toBe(4);
});

it('caps at level 99 with xp set to 0', function () {
    $user = makeUser(['account_level' => 99, 'account_xp' => 0]);
    $svc  = app(XpService::class);

    $svc->award($user, 99999);

    expect($user->fresh())
        ->account_level->toBe(99)
        ->account_xp->toBe(0);
});

it('ignores zero or negative xp gracefully', function () {
    $user = makeUser(['account_level' => 5, 'account_xp' => 50]);
    $svc  = app(XpService::class);

    $result = $svc->award($user, 0);

    expect($user->fresh()->account_xp)->toBe(50);
    expect($result['xp_gained'])->toBe(0);
});

it('exposes a deterministic threshold formula', function () {
    $svc = app(XpService::class);

    expect($svc->thresholdFor(1))->toBe(100);
    expect($svc->thresholdFor(10))->toBe(1000);
    expect($svc->thresholdFor(99))->toBe(9900);
});
