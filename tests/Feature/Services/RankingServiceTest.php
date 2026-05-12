<?php

use App\Services\RankingService;

it('returns bronze for 0 points', function () {
    expect(app(RankingService::class)->tierFor(0))->toBe('bronze');
});

it('returns silver at 200', function () {
    expect(app(RankingService::class)->tierFor(200))->toBe('silver');
});

it('returns gold at 500', function () {
    expect(app(RankingService::class)->tierFor(500))->toBe('gold');
});

it('returns platinum at 1000', function () {
    expect(app(RankingService::class)->tierFor(1000))->toBe('platinum');
});

it('returns diamond at 1500', function () {
    expect(app(RankingService::class)->tierFor(1500))->toBe('diamond');
});

it('returns master at 2200', function () {
    expect(app(RankingService::class)->tierFor(2200))->toBe('master');
});

it('returns master for very high points', function () {
    expect(app(RankingService::class)->tierFor(100000))->toBe('master');
});

it('handles negative points defensively', function () {
    expect(app(RankingService::class)->tierFor(-50))->toBe('bronze');
});

it('returns next threshold for bronze', function () {
    expect(app(RankingService::class)->nextTierThreshold(50))->toBe(200);
});

it('returns next threshold for diamond', function () {
    expect(app(RankingService::class)->nextTierThreshold(1700))->toBe(2200);
});

it('returns null for master (no next tier)', function () {
    expect(app(RankingService::class)->nextTierThreshold(5000))->toBeNull();
});

it('caps loss delta to floor at 0', function () {
    $svc = app(RankingService::class);
    expect($svc->pointsDelta(false, false, 5))->toBe(-5);
    expect($svc->pointsDelta(false, false, 0))->toBe(0);
});

it('combines win + mvp correctly', function () {
    $svc = app(RankingService::class);
    expect($svc->pointsDelta(true, true, 100))
        ->toBe(RankingService::POINTS_WIN + RankingService::POINTS_MVP);
});
