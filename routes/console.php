<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── SEO : sitemap.xml quotidien ────────────────────────────────────
Schedule::command('sitemap:rebuild')
    ->dailyAt('04:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sitemap.log'));
