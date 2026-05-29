<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PlayerApiController;
use App\Http\Controllers\Api\UnityApiController;
use Illuminate\Support\Facades\Route;

// ── API publique (sanity check) ─────────────────────────────────────
Route::get('/health', fn() => response()->json(['status' => 'ok', 'version' => config('app.version')]));

// ── Auth API (Sanctum) ──────────────────────────────────────────────
// Utilisée par Unity WebGL pour s'authentifier
Route::post('/auth/token', [AuthApiController::class, 'issueToken'])
    ->middleware('throttle:10,1')
    ->name('api.auth.token');

Route::post('/auth/revoke', [AuthApiController::class, 'revokeToken'])
    ->middleware('auth:sanctum')
    ->name('api.auth.revoke');

// ── Routes protégées Sanctum ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'not.banned'])->group(function () {

    // Profil joueur (utilisé par Unity pour récupérer les données)
    Route::get('/player/me', [PlayerApiController::class, 'me'])->name('api.player.me');

    // Unity WebGL — communication jeu ↔ serveur
    Route::prefix('unity')->name('api.unity.')->group(function () {
        Route::post('/session/start',    [UnityApiController::class, 'startSession'])->name('session.start');
        Route::post('/session/validate', [UnityApiController::class, 'validateSession'])->name('session.validate');
        Route::post('/match/result',     [UnityApiController::class, 'submitMatchResult'])
            ->middleware('throttle:30,60')
            ->name('match.result');
        Route::post('/score/submit',     [UnityApiController::class, 'submitScore'])->name('score.submit');
    });
});
