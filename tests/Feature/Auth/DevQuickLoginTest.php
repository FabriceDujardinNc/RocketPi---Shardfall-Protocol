<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    // Le quick login dev exige host whitelisté + DEV_LOGIN_PASSWORD non vide.
    // Laravel teste via `url()` qui dérive du URL generator bootstrappé depuis
    // app.url ; `config()->set('app.url', ...)` ne le met pas à jour, il faut
    // forcer la root URL pour que les requêtes de test partent du bon host.
    URL::forceRootUrl('http://rocketpi-test.pro');
    config()->set('auth.dev_login.password', 's3cret-dev');
    config()->set('auth.dev_login.allowed_hosts', ['rocketpi-test.pro']);
});

it('unlock endpoint 404 when feature disabled (no password configured)', function () {
    config()->set('auth.dev_login.password', null);

    $this->post('/dev-login/unlock', ['dev_password' => 'anything'])
        ->assertNotFound();
});

it('unlock endpoint 404 when host is not whitelisted', function () {
    // Whitelist ne contient PAS le host de la requête (rocketpi-test.pro).
    config()->set('auth.dev_login.allowed_hosts', ['some-other-domain.test']);

    $this->post('/dev-login/unlock', ['dev_password' => 's3cret-dev'])
        ->assertNotFound();
});

it('unlock rejects wrong password without setting session flag', function () {
    $this->from('/login')
        ->post('/dev-login/unlock', ['dev_password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['dev_password'])
        ->assertSessionMissing('dev_login.unlocked');
});

it('unlock sets the session flag when password matches', function () {
    $this->post('/dev-login/unlock', ['dev_password' => 's3cret-dev'])
        ->assertRedirect()
        ->assertSessionHas('dev_login.unlocked', true);
});

it('quick login is rejected when session is not unlocked', function () {
    $u = makeUser();

    $this->post('/login', [
        'user_id' => $u->id,
        'dev'     => true,
    ])->assertSessionHasErrors(['email']); // tombe sur le flow standard → email manquant

    expect(auth()->check())->toBeFalse();
});

it('quick login authenticates a player after unlock without password', function () {
    $u = makeUser();
    session()->put('dev_login.unlocked', true);

    $this->post('/login', [
        'user_id' => $u->id,
        'dev'     => true,
    ])->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($u->id);
});

it('bypass 2FA flag is ignored on a non-whitelisted host', function () {
    $admin = makeUser([
        'role' => User::ROLE_ADMIN,
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    // Session porte le flag bypass, mais la whitelist exclut le host courant.
    config()->set('auth.dev_login.allowed_hosts', ['some-other-domain.test']);

    $this->actingAs($admin)
        ->withSession(['2fa.bypass' => true])
        ->get('/admin')
        ->assertRedirect(route('2fa.setup'));
});

it('quick login bypasses 2FA setup for admins (no redirect to /2fa/setup)', function () {
    $admin = makeUser([
        'role' => User::ROLE_ADMIN,
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);
    session()->put('dev_login.unlocked', true);

    $this->post('/login', [
        'user_id' => $admin->id,
        'dev'     => true,
    ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('2fa.bypass', true);

    // Session porte le flag de bypass — vérifie qu'on peut taper /admin
    // sans être renvoyé vers le setup.
    $this->get('/admin')->assertOk();
});

it('quick login is rejected when feature is disabled even with dev=1 flag', function () {
    config()->set('auth.dev_login.password', null);
    $u = makeUser();
    session()->put('dev_login.unlocked', true);

    // Pas d'erreur 2FA bypass, on retombe sur le flow standard qui exige password.
    $this->post('/login', [
        'user_id' => $u->id,
        'dev'     => true,
    ])->assertSessionHasErrors(['email', 'password']);

    expect(auth()->check())->toBeFalse();
});

it('does not expose devUsers via Inertia share when not unlocked', function () {
    $this->get('/login')
        ->assertInertia(fn ($p) => $p
            ->where('devLogin.enabled', true)
            ->where('devLogin.unlocked', false)
            ->where('devUsers', null)
        );
});

it('exposes devUsers with masked emails once unlocked', function () {
    makeUser(['email' => 'someone@example.com']);
    session()->put('dev_login.unlocked', true);

    $this->get('/login')
        ->assertInertia(fn ($p) => $p
            ->where('devLogin.unlocked', true)
            ->has('devUsers', fn ($users) => $users
                ->each(fn ($u) => $u
                    ->has('id')
                    ->has('email_masked')
                    ->has('display_name')
                    ->has('name')
                    ->has('role')
                    ->has('is_banned')
                    ->missing('email')
                )
            )
        );
});

it('masks emails with a stable shape (head + dots)', function () {
    expect(\App\Http\Middleware\HandleInertiaRequests::maskEmail('someone@example.com'))->toBe('som…@e….com');
    expect(\App\Http\Middleware\HandleInertiaRequests::maskEmail('ab@x.io'))->toBe('a…@x….io');
    expect(\App\Http\Middleware\HandleInertiaRequests::maskEmail('admin@localhost'))->toBe('adm…@l…');
});
