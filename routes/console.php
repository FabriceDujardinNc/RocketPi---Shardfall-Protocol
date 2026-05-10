<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Leaderboard resets ──────────────────────────────────────────────
// Snapshot Redis → MySQL puis distribue les rewards des saisons expirées.
// `--expired-only` garantit qu'on ne touche qu'aux saisons dont ends_at
// est passé, donc les schedules sont safe même s'ils se chevauchent.

Schedule::command('leaderboard:reset --type=weekly --expired-only')
    ->weeklyOn(1, '00:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/leaderboard-reset.log'));

Schedule::command('leaderboard:reset --type=monthly --expired-only')
    ->monthlyOn(1, '00:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/leaderboard-reset.log'));

// Filet de sécurité quotidien : rattrape n'importe quelle saison expirée
// non encore reset (faction, seasonal, collection, annual, ou rattrapage
// si une exécution hebdo/mensuelle a échoué).
Schedule::command('leaderboard:reset --expired-only')
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/leaderboard-reset.log'));
