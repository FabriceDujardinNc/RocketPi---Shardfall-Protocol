<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Player\AchievementController;
use App\Http\Controllers\Player\DashboardController;
use App\Http\Controllers\Player\CollectionController;
use App\Http\Controllers\Player\DailyLoginController;
use App\Http\Controllers\Player\GachaController;
use App\Http\Controllers\Player\OperatorController;
use App\Http\Controllers\Player\LeaderboardController;
use App\Http\Controllers\Player\MissionController;
use App\Http\Controllers\Player\ReferralController;
use App\Http\Controllers\Player\ShopController;
use App\Http\Controllers\Player\ProfileController;
use App\Http\Controllers\Player\BattlePassController;
use App\Http\Controllers\Player\PlayController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOperatorController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminGachaLogController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\AdminLeaderboardController;
use App\Http\Controllers\Admin\AdminMissionController;
use App\Http\Controllers\Admin\AdminSettingsController;
use Illuminate\Support\Facades\Route;

// ── Public routes ───────────────────────────────────────────────────

Route::get('/', fn() => inertia('Public/Landing'))->name('home');

// Page de parrainage publique : stocke le code en session puis redirige vers /register
Route::get('/r/{code}', function (string $code, \Illuminate\Http\Request $request) {
    $request->session()->put('referral_code', $code);
    return redirect()->route('register');
})->name('referral.public');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}',  [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Email verification
Route::get('/email/verify',             fn() => inertia('Auth/VerifyEmail'))->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/resend',            [AuthController::class, 'resendVerification'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// ── Authenticated player routes ─────────────────────────────────────
// Nécessite : auth + email vérifié + pas banni
Route::middleware(['auth', 'verified', 'not.banned'])->group(function () {

    Route::get('/dashboard',   [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/collection',  [CollectionController::class, 'index'])->name('collection');
    Route::get('/operators/{operator}', [OperatorController::class, 'show'])->name('operators.show');

    // Achievements
    Route::get('/achievements',                 [AchievementController::class, 'index'])->name('achievements');
    Route::post('/achievements/{userAchievement}/claim', [AchievementController::class, 'claim'])->name('achievements.claim');

    // Daily login reward claim
    Route::post('/daily-login/claim', [DailyLoginController::class, 'claim'])->name('daily-login.claim');

    // Gacha
    Route::get('/gacha',                 [GachaController::class, 'index'])->name('gacha');
    Route::get('/gacha/{banner}',        [GachaController::class, 'show'])->name('gacha.banner');
    Route::post('/gacha/{banner}/pull',  [GachaController::class, 'pull'])
        ->middleware('throttle:60,60')
        ->name('gacha.pull');

    // Classements
    Route::get('/leaderboard',           [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('/leaderboard/{season}',  [LeaderboardController::class, 'show'])->name('leaderboard.season');

    // Missions
    Route::get('/missions', [MissionController::class, 'index'])->name('missions');
    Route::post('/missions/{mission}/claim', [MissionController::class, 'claim'])->name('missions.claim');

    // Parrainage
    Route::get('/referral',  [ReferralController::class, 'index'])->name('referral');
    Route::post('/referral/{reward}/claim', [ReferralController::class, 'claim'])->name('referral.claim');

    // Shop
    Route::get('/shop',          [ShopController::class, 'index'])->name('shop');
    Route::post('/shop/purchase',[ShopController::class, 'purchase'])->name('shop.purchase');

    // Battle Pass
    Route::get('/battlepass',                            [BattlePassController::class, 'index'])->name('battlepass');
    Route::post('/battlepass/{battlePass}/purchase',     [BattlePassController::class, 'purchase'])->name('battlepass.purchase');
    Route::post('/battlepass/tier/{tier}/claim',         [BattlePassController::class, 'claim'])->name('battlepass.claim');

    // Profil
    Route::get('/profile',         [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/{user}',  [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile',       [ProfileController::class, 'update'])->name('profile.update');

    // Jeu Unity WebGL (phase 4)
    Route::get('/play', [PlayController::class, 'index'])->name('play');
});

// ── Admin routes ────────────────────────────────────────────────────
// Middleware admin vérifie role=admin|super_admin + logs les accès
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Opérateurs
    Route::resource('operators', AdminOperatorController::class);

    // Bannières
    Route::resource('banners', AdminBannerController::class);
    Route::post('banners/{banner}/activate', [AdminBannerController::class, 'activate'])->name('banners.activate');

    // Joueurs
    Route::get('players',                    [AdminPlayerController::class, 'index'])->name('players.index');
    Route::get('players/{user}',             [AdminPlayerController::class, 'show'])->name('players.show');
    Route::post('players/{user}/ban',        [AdminPlayerController::class, 'ban'])->name('players.ban');
    Route::post('players/{user}/unban',      [AdminPlayerController::class, 'unban'])->name('players.unban');
    Route::post('players/{user}/currency',   [AdminPlayerController::class, 'grantCurrency'])->name('players.currency');

    // Logs Gacha (audit légal)
    Route::get('gacha-logs',                 [AdminGachaLogController::class, 'index'])->name('gacha-logs.index');
    Route::get('gacha-logs/export',          [AdminGachaLogController::class, 'export'])->name('gacha-logs.export');

    // Parrainages
    Route::get('referrals',                  [AdminReferralController::class, 'index'])->name('referrals.index');
    Route::post('referrals/{referral}/flag', [AdminReferralController::class, 'flag'])->name('referrals.flag');

    // Classements
    Route::get('leaderboards',               [AdminLeaderboardController::class, 'index'])->name('leaderboards.index');
    Route::get('leaderboards/{season}',      [AdminLeaderboardController::class, 'show'])->name('leaderboards.show');
    Route::post('leaderboards/{season}/reset',[AdminLeaderboardController::class, 'reset'])->name('leaderboards.reset');

    // Missions & événements
    Route::resource('missions', AdminMissionController::class);

    // Paramètres globaux
    Route::get('settings',   [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::patch('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});
