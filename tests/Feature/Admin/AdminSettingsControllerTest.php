<?php

use App\Models\Setting;
use App\Models\User;

function adminUserS(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/settings')->assertForbidden();
});

it('lists settings', function () {
    Setting::put('gacha_enabled', true, 'bool', 'Gacha');
    Setting::put('maintenance_mode', false, 'bool', 'Maintenance');

    $this->actingAs(adminUserS())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Settings')->has('settings', 2));
});

it('updates known settings, ignores unknown keys', function () {
    Setting::put('gacha_enabled', true, 'bool');
    Setting::put('maintenance_message', 'old', 'string');

    $this->actingAs(adminUserS())
        ->patch('/admin/settings', [
            'values' => [
                'gacha_enabled'       => false,
                'maintenance_message' => 'on a un problème',
                'unknown_key'         => 'noise',  // doit être ignoré
            ],
        ])
        ->assertRedirect();

    expect(Setting::value('gacha_enabled'))->toBeFalse();
    expect(Setting::value('maintenance_message'))->toBe('on a un problème');
    expect(Setting::find('unknown_key'))->toBeNull();
});

it('Setting::value caches reads (and invalidates on save)', function () {
    Setting::put('gacha_enabled', true, 'bool');
    expect(Setting::value('gacha_enabled'))->toBeTrue();

    // Update via Setting::put → cache invalidé → read suivant retourne la nouvelle valeur
    Setting::put('gacha_enabled', false, 'bool');
    expect(Setting::value('gacha_enabled'))->toBeFalse();
});
