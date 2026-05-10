<?php

use App\Models\OperatorAffinity;
use App\Services\AffinityService;

beforeEach(function () {
    $this->op = makeOperator('common');
});

it('creates an affinity row on first award', function () {
    $user = makeUser();

    expect(OperatorAffinity::count())->toBe(0);

    app(AffinityService::class)->award($user, $this->op, 50);

    expect(OperatorAffinity::count())->toBe(1);
    expect(OperatorAffinity::first())
        ->level->toBe(0)
        ->xp_current->toBe(50);
});

it('levels up when xp reaches threshold', function () {
    // Niveau 0 → 1 = 100 XP requis
    $user = makeUser();

    app(AffinityService::class)->award($user, $this->op, 120);

    $aff = OperatorAffinity::first();
    expect($aff->level)->toBe(1);     // 100 XP consommés, 20 reste
    expect($aff->xp_current)->toBe(20);
});

it('cascades multiple level ups on a big award', function () {
    // Niv 0→1=100, 1→2=200, 2→3=300 → total 600 → niv 3
    $user = makeUser();

    $r = app(AffinityService::class)->award($user, $this->op, 600);

    expect(OperatorAffinity::first())
        ->level->toBe(3)
        ->xp_current->toBe(0);
    expect($r['leveled_up'])->toBeTrue();
    expect($r['new_level'])->toBe(3);
});

it('caps at level 10 and resets xp to 0', function () {
    $user = makeUser();

    app(AffinityService::class)->award($user, $this->op, 999999);

    expect(OperatorAffinity::first())
        ->level->toBe(AffinityService::MAX_LEVEL)
        ->xp_current->toBe(0);
});

it('ignores zero or negative xp', function () {
    $user = makeUser();

    $r = app(AffinityService::class)->award($user, $this->op, 0);

    expect(OperatorAffinity::count())->toBe(0);
    expect($r['xp_gained'])->toBe(0);
});

it('exposes deterministic threshold formula', function () {
    $svc = app(AffinityService::class);

    expect($svc->thresholdFor(0))->toBe(100);
    expect($svc->thresholdFor(5))->toBe(600);
    expect($svc->thresholdFor(9))->toBe(1000);
});

it('uses separate affinity rows per operator', function () {
    $user = makeUser();
    $op2  = makeOperator('rare');

    app(AffinityService::class)->award($user, $this->op, 100);
    app(AffinityService::class)->award($user, $op2, 50);

    expect(OperatorAffinity::count())->toBe(2);
});
