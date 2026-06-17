<?php

use App\Models\User;
use App\Services\TwoFactorService;
use PragmaRX\Google2FA\Google2FA;

it('admin without 2fa is redirected to setup when hitting /admin', function () {
    // makeUser auto-configure la 2FA pour les admins en tests — on l'annule
    // explicitement pour vérifier le flow de forçage du setup.
    $admin = makeUser([
        'role' => User::ROLE_ADMIN,
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);
    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('2fa.setup'));
});

it('admin with 2fa enabled and passed in session can access /admin', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    app(TwoFactorService::class)->confirm(
        $admin,
        $g2fa = (new Google2FA())->generateSecretKey(),
        (new Google2FA())->getCurrentOtp($g2fa),
    );
    $this->actingAs($admin)->withSession(['2fa.passed' => true])
        ->get('/admin')
        ->assertOk();
});

it('admin with 2fa enabled but session not passed → challenge', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $svc->confirm($admin, $secret, (new Google2FA())->getCurrentOtp($secret));

    // Le beforeEach met 2fa.passed=true par défaut — on l'efface ici pour
    // vérifier que le middleware redirige vers /2fa/challenge sans la session.
    $this->actingAs($admin)
        ->withSession(['2fa.passed' => false])
        ->get('/admin')
        ->assertRedirect(route('2fa.challenge'));
});

it('non-admin player can access /dashboard without 2fa', function () {
    $u = makeUser();
    $this->actingAs($u)->get('/dashboard')->assertOk();
});

it('shows setup page with secret and QR svg', function () {
    $u = makeUser();
    $this->actingAs($u)
        ->get('/2fa/setup')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Auth/TwoFactorSetup')
            ->has('secret')
            ->has('qrSvg')
        );
});

it('confirms 2fa with valid TOTP and stores recovery codes', function () {
    $u = makeUser();
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();

    $this->actingAs($u)
        ->withSession(['2fa.pending_secret' => $secret])
        ->post('/2fa/setup', ['code' => (new Google2FA())->getCurrentOtp($secret)])
        ->assertRedirect(route('2fa.recovery'));

    $u->refresh();
    expect($u->two_factor_secret)->toBe($secret);
    expect($u->two_factor_confirmed_at)->not->toBeNull();
    expect(count($u->two_factor_recovery_codes ?? []))->toBe(8);
});

it('rejects 2fa setup with invalid TOTP', function () {
    $u = makeUser();
    $secret = app(TwoFactorService::class)->generateSecret();

    $this->actingAs($u)
        ->withSession(['2fa.pending_secret' => $secret])
        ->post('/2fa/setup', ['code' => '000000'])
        ->assertSessionHasErrors(['code']);

    expect($u->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('challenge accepts valid TOTP', function () {
    $u = makeUser();
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $svc->confirm($u, $secret, (new Google2FA())->getCurrentOtp($secret));

    $this->actingAs($u)
        ->post('/2fa/challenge', ['code' => (new Google2FA())->getCurrentOtp($secret)])
        ->assertRedirect();
});

it('challenge accepts a recovery code and burns it', function () {
    $u = makeUser();
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $recovery = $svc->confirm($u, $secret, (new Google2FA())->getCurrentOtp($secret));

    $first = $recovery[0];

    $this->actingAs($u)
        ->post('/2fa/challenge', ['recovery' => $first])
        ->assertRedirect();

    // Code brûlé : second appel direct au service échoue.
    expect($svc->consumeRecoveryCode($u->fresh(), $first))->toBeFalse();
});

it('challenge rejects invalid code', function () {
    $u = makeUser();
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $svc->confirm($u, $secret, (new Google2FA())->getCurrentOtp($secret));

    $this->actingAs($u)
        ->post('/2fa/challenge', ['code' => '000000'])
        ->assertSessionHasErrors(['code']);
});

it('disable refuses for admin users', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $svc->confirm($admin, $secret, (new Google2FA())->getCurrentOtp($secret));

    $this->actingAs($admin)
        ->withSession(['2fa.passed' => true])
        ->post('/2fa/disable')
        ->assertSessionHasErrors(['2fa']);

    expect($admin->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

it('disable works for non-admin users', function () {
    $u = makeUser();
    $svc = app(TwoFactorService::class);
    $secret = $svc->generateSecret();
    $svc->confirm($u, $secret, (new Google2FA())->getCurrentOtp($secret));

    $this->actingAs($u)
        ->withSession(['2fa.passed' => true])
        ->post('/2fa/disable')
        ->assertRedirect();

    expect($u->fresh()->two_factor_confirmed_at)->toBeNull();
    expect($u->fresh()->two_factor_secret)->toBeNull();
});
