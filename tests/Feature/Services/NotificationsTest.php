<?php

use App\Models\MatchSession;
use App\Models\User;
use App\Notifications\RankPromoted;
use App\Notifications\SeasonEndingSoon;
use App\Services\MatchService;
use App\Services\RankingService;
use Illuminate\Support\Facades\Notification;

it('fires RankPromoted when player crosses a tier on win', function () {
    Notification::fake();

    // User à 199 pts (Bronze) → win = 199 + 25 = 224 (Silver)
    $u = makeUser(['rank_points' => 199]);
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $svc->finish($start['session_token'], [
        'score' => 1000, 'won' => true, 'duration_seconds' => 300,
    ]);

    Notification::assertSentTo($u, RankPromoted::class, function ($notif) {
        return $notif->previousTier === 'bronze' && $notif->newTier === 'silver';
    });
});

it('does not fire RankPromoted when staying within same tier', function () {
    Notification::fake();
    $u = makeUser(['rank_points' => 100]); // Bronze
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $svc->finish($start['session_token'], [
        'score' => 500, 'won' => true, 'duration_seconds' => 300,
    ]);
    // 100 + 25 = 125 → toujours Bronze

    Notification::assertNothingSentTo($u);
});

it('does not fire RankPromoted on demotion', function () {
    Notification::fake();
    $u = makeUser(['rank_points' => 210]); // Silver
    $svc = app(MatchService::class);
    $start = $svc->start($u, 'deathmatch', 'ranked');
    MatchSession::where('id', $start['session']->id)->update(['started_at' => now()->subMinutes(5)]);

    $svc->finish($start['session_token'], [
        'score' => 100, 'won' => false, 'duration_seconds' => 300,
    ]);
    // 210 - 15 = 195 → Bronze (démotion) — pas de notif

    Notification::assertNothingSentTo($u);
});
