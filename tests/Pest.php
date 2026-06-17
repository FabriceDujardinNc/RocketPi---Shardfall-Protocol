<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    // Tous les tests Admin/* sont supposés avoir déjà passé le 2FA — le
    // middleware EnsureTwoFactorPassed bloquerait sinon les admin sans
    // setup TOTP. On marque la session comme déjà validée.
    ->beforeEach(function () {
        session()->put('2fa.passed', true);
    })
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function makeUser(array $attrs = []): \App\Models\User
{
    $defaults = [
        'role'              => \App\Models\User::ROLE_USER,
        'account_level'     => 1,
        'account_xp'        => 0,
        'email_verified_at' => now(),
    ];

    // En tests, les users admin sont créés avec 2FA déjà confirmée pour
    // simplifier — le flow 2FA est testé séparément dans TwoFactorTest.
    $role = $attrs['role'] ?? $defaults['role'];
    if (in_array($role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
        $defaults['two_factor_secret'] = 'TESTONLY2FASECRET32CHARSXXXXXXXX';
        $defaults['two_factor_confirmed_at'] = now();
        $defaults['two_factor_recovery_codes'] = ['TEST1-TEST1'];
    }

    return \App\Models\User::factory()->create(array_merge($defaults, $attrs));
}

function makeOperator(string $rarity = 'common', string $faction = 'ORBIT'): \App\Models\Operator
{
    static $counter = 0;
    $counter++;

    return \App\Models\Operator::create([
        'name'                   => "Op{$counter}",
        'codename'               => sprintf('OP-%04d', $counter),
        'faction'                => $faction,
        'role'                   => 'assault',
        'rarity'                 => $rarity,
        'is_available'           => true,
        'is_rate_up'             => false,
        'base_rig_version'       => 'humanoid-v1',
        'base_generation_status' => 'pending',
    ]);
}

function makeBanner(array $attrs = []): \App\Models\Banner
{
    return \App\Models\Banner::create(array_merge([
        'name'             => 'Test Banner',
        'type'             => 'permanent',
        'rate_legendary'   => 0.02,
        'rate_epic'        => 0.08,
        'rate_rare'        => 0.30,
        'rate_common'      => 0.60,
        'pity_legendary'   => 80,
        'soft_pity_start'  => 60,
        'pity_epic'        => 10,
        'is_active'        => true,
    ], $attrs));
}

function giveCurrency(\App\Models\User $user, string $type, int $amount): \App\Models\Currency
{
    return \App\Models\Currency::create([
        'user_id' => $user->id,
        'type'    => $type,
        'balance' => $amount,
    ]);
}
