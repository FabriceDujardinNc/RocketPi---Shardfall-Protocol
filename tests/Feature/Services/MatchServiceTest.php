<?php

use App\Models\MatchResult;
use App\Models\MatchSession;
use App\Services\MatchService;
use App\Services\RankingService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

afterAll(function () {
    Redis::flushdb();
});

it('starts a casual session and returns a 64-char token', function () {
    $u = makeUser();
    $svc = app(MatchService::class);

    $result = $svc->start($u, 'deathmatch', 'casual');

    expect($result['session_token'])->toBeString()->toHaveLength(64);
    expect($result['session']->status)->toBe('started');
    expect($result['session']->rank_type)->toBe('casual');
});

it('blocks ranked start when daily limit reached', function () {
    $u = makeUser([
        'daily_matches_played' => RankingService::DAILY_MATCH_LIMIT,
        'daily_matches_reset_at' => now()->toDateString(),
    ]);
    $svc = app(MatchService::class);

    expect(fn () => $svc->start($u, 'deathmatch', 'ranked'))
        ->toThrow(RuntimeException::class, 'Limite quotidienne');
});

it('resets daily counter at midnight UTC', function () {
    $u = makeUser([
        'daily_matches_played' => 50,
        'daily_matches_reset_at' => now()->subDay()->toDateString(),
    ]);
    $svc = app(MatchService::class);

    $svc->start($u, 'deathmatch', 'ranked');

    expect($u->fresh()->daily_matches_played)->toBe(0);
});

it('blocks start with operator not owned', function () {
    $u = makeUser();
    $op = makeOperator();
    $svc = app(MatchService::class);

    expect(fn () => $svc->start($u, 'deathmatch', 'casual', $op->id))
        ->toThrow(RuntimeException::class, 'opérateur');
});

it('finishes a match with valid payload and awards rank points on win', function () {
    $u = makeUser(['rank_points' => 100]);
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    // Simule un match passé : back-date pour passer le min duration
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $result = $svc->finish($start['session_token'], [
        'score' => 1500, 'kills' => 12, 'deaths' => 4, 'assists' => 6,
        'won' => true, 'is_mvp' => false, 'duration_seconds' => 300,
    ]);

    expect($result->won)->toBeTrue();
    expect($result->rank_points_delta)->toBe(RankingService::POINTS_WIN);
    expect($u->fresh()->rank_points)->toBe(100 + RankingService::POINTS_WIN);
    expect($u->fresh()->daily_matches_played)->toBe(1);
});

it('adds MVP bonus on top of win', function () {
    $u = makeUser(['rank_points' => 0]);
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $result = $svc->finish($start['session_token'], [
        'score' => 2000, 'kills' => 18, 'deaths' => 2, 'assists' => 4,
        'won' => true, 'is_mvp' => true, 'duration_seconds' => 300,
    ]);

    expect($result->rank_points_delta)->toBe(RankingService::POINTS_WIN + RankingService::POINTS_MVP);
});

it('floors rank_points at 0 on loss', function () {
    $u = makeUser(['rank_points' => 5]);
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $svc->finish($start['session_token'], [
        'score' => 100, 'won' => false, 'duration_seconds' => 300,
    ]);

    expect($u->fresh()->rank_points)->toBe(0);
});

it('rejects impossible score for short duration (anti-cheat)', function () {
    $u = makeUser();
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'casual');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subSeconds(60)]);

    // 60s × 100 pts/sec = 6000 max ; on envoie 100000 → reject
    expect(fn () => $svc->finish($start['session_token'], [
        'score' => 100000, 'won' => true, 'duration_seconds' => 60,
    ]))->toThrow(RuntimeException::class, 'Score impossible');

    // La session est invalidée pour éviter retry
    expect(MatchSession::where('session_token', $start['session_token'])->value('status'))
        ->not->toBe('finished');
});

it('rejects impossible kills count (anti-cheat)', function () {
    $u = makeUser();
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'casual');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subSeconds(60)]);

    // 60s × 1.5 = 90 max ; on envoie 200 → reject
    expect(fn () => $svc->finish($start['session_token'], [
        'score' => 100, 'kills' => 200, 'won' => true, 'duration_seconds' => 60,
    ]))->toThrow(RuntimeException::class, 'Kills impossibles');
});

it('rejects duration too short', function () {
    $u = makeUser();
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'casual');

    expect(fn () => $svc->finish($start['session_token'], [
        'score' => 100, 'won' => true, 'duration_seconds' => 5,
    ]))->toThrow(RuntimeException::class, 'trop courte');
});

it('rejects double-finish on same session', function () {
    $u = makeUser();
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'casual');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $svc->finish($start['session_token'], ['score' => 100, 'won' => true, 'duration_seconds' => 300]);

    expect(fn () => $svc->finish($start['session_token'], ['score' => 100, 'won' => true, 'duration_seconds' => 300]))
        ->toThrow(RuntimeException::class, 'clôturée');
});

it('rejects unknown session token', function () {
    $svc = app(MatchService::class);
    expect(fn () => $svc->finish('0000', ['score' => 100, 'duration_seconds' => 300]))
        ->toThrow(RuntimeException::class, 'inconnue');
});

it('marks zombie sessions as abandoned on new start', function () {
    $u = makeUser();
    $svc = app(MatchService::class);

    $zombie = MatchSession::create([
        'user_id' => $u->id,
        'session_token' => str_repeat('a', 64),
        'mode' => 'deathmatch',
        'rank_type' => 'casual',
        'status' => 'started',
        'started_at' => now()->subHour(), // > TTL
    ]);

    $svc->start($u, 'deathmatch', 'casual');

    expect($zombie->fresh()->status)->toBe('abandoned');
});

it('abandon counts as loss in ranked', function () {
    $u = makeUser(['rank_points' => 100]);
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');

    $svc->abandon($start['session_token']);

    expect($start['session']->fresh()->status)->toBe('abandoned');
    expect($u->fresh()->rank_points)->toBe(100 + RankingService::POINTS_LOSS);
    expect(MatchResult::where('match_session_id', $start['session']->id)->count())->toBe(1);
});
