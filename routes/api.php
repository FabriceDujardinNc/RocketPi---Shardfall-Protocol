<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\GachaApiController;
use App\Http\Controllers\Api\LeaderboardApiController;
use App\Http\Controllers\Api\MissionApiController;
use App\Http\Controllers\Api\PlayerApiController;
use App\Http\Controllers\Api\UnityApiController;
use Illuminate\Support\Facades\Route;

// ── API publique (sanity check) ─────────────────────────────────────
Route::get('/health', fn() => response()->json(['status' => 'ok', 'version' => config('app.version')]));

// ── Auth API (Sanctum) ──────────────────────────────────────────────
// Utilisée par Unity WebGL pour s'authentifier
Route::post('/auth/token', [AuthApiController::class, 'issueToken'])
    ->middleware('throttle:10,1')   // Max 10 tentatives/minute (brute force protection)
    ->name('api.auth.token');

Route::post('/auth/revoke', [AuthApiController::class, 'revokeToken'])
    ->middleware('auth:sanctum')
    ->name('api.auth.revoke');

// ── Routes protégées Sanctum ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'not.banned'])->group(function () {

    // Profil joueur (utilisé par Unity pour récupérer les données)
    Route::get('/player/me',        [PlayerApiController::class, 'me'])->name('api.player.me');
    Route::get('/player/operators', [PlayerApiController::class, 'operators'])->name('api.player.operators');
    Route::get('/player/currencies',[PlayerApiController::class, 'currencies'])->name('api.player.currencies');

    // Gacha — tirage 100% côté serveur
    // Rate limiting strict : max 60 tirages/heure (protection économique)
    Route::post('/gacha/pull',      [GachaApiController::class, 'pull'])
        ->middleware('throttle:60,60')
        ->name('api.gacha.pull');
    Route::get('/gacha/pity/{banner}', [GachaApiController::class, 'pityStatus'])->name('api.gacha.pity');
    Route::get('/gacha/history',    [GachaApiController::class, 'history'])->name('api.gacha.history');

    // Missions
    Route::get('/missions',             [MissionApiController::class, 'index'])->name('api.missions.index');
    Route::post('/missions/{id}/claim', [MissionApiController::class, 'claim'])->name('api.missions.claim');

    // Classements
    Route::get('/leaderboard/{season}',       [LeaderboardApiController::class, 'top100'])->name('api.leaderboard.top');
    Route::get('/leaderboard/{season}/me',    [LeaderboardApiController::class, 'playerRank'])->name('api.leaderboard.me');
    Route::get('/leaderboard/{season}/around',[LeaderboardApiController::class, 'around'])->name('api.leaderboard.around');

    // Unity WebGL — communication jeu ↔ serveur
    // Tokens de session signés (Sanctum), validation autoritaire des résultats
    Route::prefix('unity')->name('api.unity.')->group(function () {
        Route::post('/session/start',    [UnityApiController::class, 'startSession'])->name('session.start');
        Route::post('/session/validate', [UnityApiController::class, 'validateSession'])->name('session.validate');
        Route::post('/match/result',     [UnityApiController::class, 'submitMatchResult'])
            ->middleware('throttle:30,60')  // Max 30 matchs/heure
            ->name('match.result');
        Route::post('/score/submit',     [UnityApiController::class, 'submitScore'])->name('score.submit');
    });
});
