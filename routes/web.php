<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Player\AchievementController;
use App\Http\Controllers\Player\DashboardController;
use App\Http\Controllers\Player\CollectionController;
use App\Http\Controllers\Player\DailyLoginController;
use App\Http\Controllers\Player\FactionController;
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
use App\Http\Controllers\Admin\AdminAccessoryController;
use App\Http\Controllers\Admin\AdminAsset3dController;
use App\Http\Controllers\Admin\AdminOperatorSkinController;
use App\Http\Controllers\Admin\AdminOperatorController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminGachaLogController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\AdminLeaderboardController;
use App\Http\Controllers\Admin\AdminMissionController;
use App\Http\Controllers\Admin\AdminSettingsController;
use Illuminate\Support\Facades\Route;

// ── SEO infrastructure ──────────────────────────────────────────────
// Sitemap dynamique (cache 6h). robots.txt est servi en statique depuis
// public/robots.txt (déjà géré par Nginx).
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// ── Public routes ───────────────────────────────────────────────────

Route::get('/', function (\Illuminate\Http\Request $request) {
    if ($user = $request->user()) {
        return redirect($user->isAdmin() ? '/admin' : '/dashboard');
    }
    return inertia('Public/Landing');
})->name('home');

// Page de parrainage publique : stocke le code en session puis redirige vers /register
Route::get('/r/{code}', function (string $code, \Illuminate\Http\Request $request) {
    $request->session()->put('referral_code', $code);
    return redirect()->route('register');
})->name('referral.public');

// ── Vitrines lore publiques (SEO) ───────────────────────────────────
// Aucune donnée joueur exposée — uniquement le contenu narratif et fiches
// opérateurs / factions. Routes accessibles guests + auth.
Route::prefix('lore')->name('lore.')->group(function () {
    Route::get('/',                       [\App\Http\Controllers\Public\LoreController::class, 'index'])->name('index');
    Route::get('/factions/{faction}',     [\App\Http\Controllers\Public\LoreController::class, 'faction'])->name('faction');
    Route::get('/operators/{operator}',   [\App\Http\Controllers\Public\LoreController::class, 'operator'])->name('operator');
});

// Classement public top 100 (lecture seule, sans auth) — argument trafic SEO.
// La page joueur authentifiée (/leaderboard) reste séparée et inclut le rang perso.
Route::get('/top', [\App\Http\Controllers\Public\LoreController::class, 'leaderboard'])->name('top');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    // Déverrouille la liste de quick login (APP_ENV=local + DEV_LOGIN_PASSWORD).
    // Throttle anti-brute force ; 404 si la feature n'est pas active.
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
// Setup obligatoire pour les admins (forcé par middleware 2fa sur /admin).
// Optionnel pour les joueurs.
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
    Route::get('/leaderboard/history',   [LeaderboardController::class, 'history'])->name('leaderboard.history');
    Route::get('/hall-of-fame',          [LeaderboardController::class, 'hallOfFame'])->name('hall-of-fame');
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
    Route::post('/shop/fragments/{operator}/redeem', [ShopController::class, 'redeemFragments'])->name('shop.fragments.redeem');

    // Signalements joueurs (Phase 4 — anti-toxicité/cheat). Rate limit 5/h.
    Route::post('/reports', [\App\Http\Controllers\Player\ReportController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('reports.store');

    // Événements limités (Phase 4)
    Route::get('/events', [\App\Http\Controllers\Player\EventController::class, 'index'])->name('events');

    // Cosmétiques — inventaire perso
    Route::get('/cosmetics',                       [\App\Http\Controllers\Player\CosmeticsController::class, 'index'])->name('cosmetics');
    Route::post('/cosmetics/{cosmetic}/equip',     [\App\Http\Controllers\Player\CosmeticsController::class, 'equip'])->name('cosmetics.equip');
    Route::post('/cosmetics/{cosmetic}/unequip',   [\App\Http\Controllers\Player\CosmeticsController::class, 'unequip'])->name('cosmetics.unequip');

    // Battle Pass
    Route::get('/battlepass',                            [BattlePassController::class, 'index'])->name('battlepass');
    Route::post('/battlepass/{battlePass}/purchase',     [BattlePassController::class, 'purchase'])->name('battlepass.purchase');
    Route::post('/battlepass/tier/{tier}/claim',         [BattlePassController::class, 'claim'])->name('battlepass.claim');

    // Profil
    Route::get('/profile',              [ProfileController::class, 'index'])->name('profile');
    // URL publique partageable, résolue par slug (ex: /profile/fabrice).
    // Le slug est auto-généré depuis display_name (cf. User::booted).
    Route::get('/profile/{user:slug}',  [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile',            [ProfileController::class, 'update'])->name('profile.update');

    // Factions — info publique + collection par faction
    Route::get('/factions',                   [FactionController::class, 'index'])->name('factions');
    Route::get('/factions/{faction:slug}',    [FactionController::class, 'show'])->name('factions.show');

    // Jeu Unity WebGL (phase 4)
    Route::get('/play', [PlayController::class, 'index'])->name('play');
});

// ── Admin routes ────────────────────────────────────────────────────
// Middleware admin vérifie role=admin|super_admin + logs les accès
Route::middleware(['auth', 'admin', '2fa'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Opérateurs
    Route::post('operators/{slug}/restore', [AdminOperatorController::class, 'restore'])
        ->name('operators.restore');
    // Asset 3D : gestion mesh + skins + accessoires d'un opérateur.
    // Déclaré AVANT le resource pour que les routes nommées ne soient pas
    // capturées par operators.show/{operator}.
    Route::get('operators/{operator:slug}/assets', [AdminAsset3dController::class, 'show'])
        ->name('operators.assets');
    Route::post('operators/{operator:slug}/assets/generate', [AdminAsset3dController::class, 'trigger'])
        ->name('operators.assets.generate');
    Route::post('operators/{operator:slug}/assets/refine', [AdminAsset3dController::class, 'refine'])
        ->name('operators.assets.refine');
    Route::resource('operators', AdminOperatorController::class);

    // Skins d'opérateur — catalogue global + génération 3D dédiée
    Route::post('skins/{skin}/generate', [AdminOperatorSkinController::class, 'generate'])
        ->name('skins.generate');
    Route::resource('skins', AdminOperatorSkinController::class)
        ->except(['show']);

    // Accessoires — catalogue global (slot/socket) + génération 3D + pivot opérateurs
    Route::post('accessories/{accessory}/generate', [AdminAccessoryController::class, 'generate'])
        ->name('accessories.generate');
    Route::resource('accessories', AdminAccessoryController::class)
        ->except(['show']);

    // Bannières
    Route::post('banners/{slug}/restore', [AdminBannerController::class, 'restore'])
        ->name('banners.restore');
    Route::post('banners/{banner}/activate', [AdminBannerController::class, 'activate'])->name('banners.activate');
    Route::resource('banners', AdminBannerController::class);

    // Joueurs
    Route::get('players',                    [AdminPlayerController::class, 'index'])->name('players.index');
    Route::get('players/{user}',             [AdminPlayerController::class, 'show'])->name('players.show');
    Route::post('players/{user}/ban',        [AdminPlayerController::class, 'ban'])->name('players.ban');
    Route::post('players/{user}/unban',      [AdminPlayerController::class, 'unban'])->name('players.unban');
    Route::post('players/{user}/currency',   [AdminPlayerController::class, 'grantCurrency'])->name('players.currency');

    // Logs Gacha (audit légal)
    Route::get('gacha-logs',                 [AdminGachaLogController::class, 'index'])->name('gacha-logs.index');
    Route::get('gacha-logs/export',          [AdminGachaLogController::class, 'export'])->name('gacha-logs.export');

    // Modération — signalements joueurs
    Route::get('moderation',                       [\App\Http\Controllers\Admin\AdminModerationController::class, 'index'])->name('moderation.index');
    Route::post('moderation/{report}/dismiss',     [\App\Http\Controllers\Admin\AdminModerationController::class, 'dismiss'])->name('moderation.dismiss');
    Route::post('moderation/{report}/sanction',    [\App\Http\Controllers\Admin\AdminModerationController::class, 'sanction'])->name('moderation.sanction');
    Route::post('moderation/{report}/reviewed',    [\App\Http\Controllers\Admin\AdminModerationController::class, 'markReviewed'])->name('moderation.reviewed');

    // Parrainages
    Route::get('referrals',                  [AdminReferralController::class, 'index'])->name('referrals.index');
    Route::post('referrals/{referral}/flag', [AdminReferralController::class, 'flag'])->name('referrals.flag');

    // Classements
    Route::post('leaderboards/{season}/reset', [AdminLeaderboardController::class, 'reset'])->name('leaderboards.reset');
    Route::resource('leaderboards', AdminLeaderboardController::class)
        ->parameters(['leaderboards' => 'season']);

    // Missions & événements
    Route::post('missions/{slug}/restore', [AdminMissionController::class, 'restore'])
        ->name('missions.restore');
    Route::resource('missions', AdminMissionController::class);

    // Battle Pass — saisons + paliers
    Route::put('battle-passes/{battle_pass}/tiers',
        [\App\Http\Controllers\Admin\AdminBattlePassController::class, 'updateTiers']
    )->name('battle-passes.tiers.update');
    Route::resource('battle-passes', \App\Http\Controllers\Admin\AdminBattlePassController::class)
        ->parameters(['battle-passes' => 'battle_pass']);

    // Récompenses de connexion quotidienne
    Route::resource('daily-login-rewards', \App\Http\Controllers\Admin\AdminDailyLoginRewardController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['daily-login-rewards' => 'daily_login_reward']);

    // Factions — lore + page collection par faction
    Route::resource('factions', \App\Http\Controllers\Admin\AdminFactionController::class)
        ->only(['index', 'show', 'edit', 'update']);

    // Achievements (honneurs)
    Route::resource('achievements', \App\Http\Controllers\Admin\AdminAchievementController::class)
        ->except(['show']);

    // Événements (limités dans le temps, optionnellement liés à une bannière)
    Route::resource('events', \App\Http\Controllers\Admin\AdminEventController::class)
        ->except(['show']);

    // Cosmétiques (catalogue : skins/titles/voicelines/banners/borders)
    Route::resource('cosmetics', \App\Http\Controllers\Admin\AdminCosmeticController::class)
        ->except(['show']);

    // Paramètres globaux
    Route::get('settings',   [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::patch('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});
