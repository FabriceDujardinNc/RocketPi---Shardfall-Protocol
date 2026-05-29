<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Player\PlayController;
use App\Http\Controllers\Player\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPlayerController;
use Illuminate\Support\Facades\Route;

// ── SEO infrastructure ──────────────────────────────────────────────
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// ── Public routes ───────────────────────────────────────────────────

Route::get('/', function (\Illuminate\Http\Request $request) {
    if ($user = $request->user()) {
        return redirect($user->isAdmin() ? '/admin' : '/play');
    }
    return inertia('Public/Landing');
})->name('home');

// Pages publiques (lecture sans auth) : dons + flux des idées de la communauté.
// TODO Phase 3 : créer DonationController + IdeaController côté Public.
// Route::get('/dons',   [\App\Http\Controllers\Public\DonationController::class, 'index'])->name('dons');
// Route::get('/idees',  [\App\Http\Controllers\Public\IdeaController::class, 'index'])->name('ideas.index');
// Route::get('/idees/{idea:slug}', [\App\Http\Controllers\Public\IdeaController::class, 'show'])->name('ideas.show');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/dev-login/unlock', [AuthController::class, 'unlockDevLogin'])
        ->middleware('throttle:5,1')
        ->name('dev-login.unlock');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}',  [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── 2FA TOTP ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/2fa/setup',     [\App\Http\Controllers\Auth\TwoFactorController::class, 'showSetup'])->name('2fa.setup');
    Route::post('/2fa/setup',    [\App\Http\Controllers\Auth\TwoFactorController::class, 'confirmSetup']);
    Route::get('/2fa/recovery',  [\App\Http\Controllers\Auth\TwoFactorController::class, 'showRecovery'])->name('2fa.recovery');
    Route::get('/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge',[\App\Http\Controllers\Auth\TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:5,1');
    Route::post('/2fa/disable',  [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('2fa.disable');
});

// Email verification
Route::get('/email/verify',             fn() => inertia('Auth/VerifyEmail'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/resend',            [AuthController::class, 'resendVerification'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// ── Authenticated user routes ───────────────────────────────────────
// Nécessite : auth + email vérifié + pas banni
Route::middleware(['auth', 'verified', 'not.banned'])->group(function () {

    // Profil — édition compte
    Route::get('/profile',              [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/{user:slug}',  [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile',            [ProfileController::class, 'update'])->name('profile.update');

    // Jeu Unity WebGL
    Route::get('/play', [PlayController::class, 'index'])->name('play');

    // Idées de développement — TODO Phase 3
    // Route::post('/idees',                [\App\Http\Controllers\Player\IdeaController::class, 'store'])->name('ideas.store');
    // Route::patch('/idees/{idea}',        [\App\Http\Controllers\Player\IdeaController::class, 'update'])->name('ideas.update');
    // Route::delete('/idees/{idea}',       [\App\Http\Controllers\Player\IdeaController::class, 'destroy'])->name('ideas.destroy');
    // Route::post('/idees/{idea}/vote',    [\App\Http\Controllers\Player\IdeaController::class, 'vote'])->name('ideas.vote');
    // Route::delete('/idees/{idea}/vote',  [\App\Http\Controllers\Player\IdeaController::class, 'unvote'])->name('ideas.unvote');
});

// ── Admin routes ────────────────────────────────────────────────────
// Site simplifié : on garde uniquement la gestion des utilisateurs et la
// modération des idées de la communauté. Le dashboard sert d'accueil admin.
Route::middleware(['auth', 'admin', '2fa'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Joueurs
    Route::get('players',                    [AdminPlayerController::class, 'index'])->name('players.index');
    Route::get('players/{user}',             [AdminPlayerController::class, 'show'])->name('players.show');
    Route::post('players/{user}/ban',        [AdminPlayerController::class, 'ban'])->name('players.ban');
    Route::post('players/{user}/unban',      [AdminPlayerController::class, 'unban'])->name('players.unban');

    // Modération des idées — TODO Phase 3
    // Route::get('ideas',                       [\App\Http\Controllers\Admin\AdminIdeaController::class, 'index'])->name('ideas.index');
    // Route::patch('ideas/{idea}/status',       [\App\Http\Controllers\Admin\AdminIdeaController::class, 'updateStatus'])->name('ideas.status');
    // Route::delete('ideas/{idea}',             [\App\Http\Controllers\Admin\AdminIdeaController::class, 'destroy'])->name('ideas.destroy');

    // Paramètres globaux (PayPal URL, adresse crypto wallet pour les dons)
    Route::get('settings',   [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
    Route::patch('settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('settings.update');
});
