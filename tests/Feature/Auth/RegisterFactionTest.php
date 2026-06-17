<?php

use App\Models\Faction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // FactionSeeder requis pour que la page register charge le catalogue
    foreach (['ORBIT', 'FERRO', 'VEIL'] as $slug) {
        Faction::firstOrCreate(['slug' => $slug], [
            'name' => $slug,
            'tagline' => "{$slug} tagline",
            'lore' => "{$slug} lore",
            'color_hue' => 100,
        ]);
    }
});

it('shows the 3 factions on the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Auth/Register')
            ->has('factions', 3)
        );
});

it('refuses registration without a faction', function () {
    $this->post('/register', [
        'name'                  => 'Recruit',
        'email'                 => 'recruit@rocketpi.local',
        'password'              => 'Sup3rs3cret!',
        'password_confirmation' => 'Sup3rs3cret!',
    ])->assertSessionHasErrors(['faction']);

    expect(User::where('email', 'recruit@rocketpi.local')->exists())->toBeFalse();
});

it('refuses an unknown faction value', function () {
    $this->post('/register', [
        'name'                  => 'Recruit',
        'email'                 => 'recruit@rocketpi.local',
        'password'              => 'Sup3rs3cret!',
        'password_confirmation' => 'Sup3rs3cret!',
        'faction'               => 'CHAOS',
    ])->assertSessionHasErrors(['faction']);
});

it('creates the user with chosen faction', function () {
    $this->post('/register', [
        'name'                  => 'Recruit',
        'email'                 => 'recruit@rocketpi.local',
        'password'              => 'Sup3rs3cret!',
        'password_confirmation' => 'Sup3rs3cret!',
        'faction'               => 'FERRO',
    ]);

    $user = User::where('email', 'recruit@rocketpi.local')->first();
    expect($user)->not->toBeNull();
    expect($user->faction)->toBe('FERRO');
});

it('blocks faction changes on existing user (immutable)', function () {
    $u = makeUser(['faction' => 'ORBIT']);

    expect(fn () => $u->update(['faction' => 'VEIL']))
        ->toThrow(RuntimeException::class, 'immuable');

    expect($u->fresh()->faction)->toBe('ORBIT');
});

it('allows setting faction on a user that had none (legacy backfill case)', function () {
    // Crée un user sans faction (simule legacy avant migration)
    $u = User::factory()->create(['faction' => null]);
    $u->update(['faction' => 'VEIL']);
    expect($u->fresh()->faction)->toBe('VEIL');
});

it('accepts all three valid factions', function () {
    foreach (['ORBIT', 'FERRO', 'VEIL'] as $faction) {
        $email = strtolower($faction) . '@rocketpi.local';
        // L'inscription connecte l'utilisateur → on logout entre les itérations
        // pour repasser dans le groupe guest.
        auth()->logout();
        $this->post('/register', [
            'name'                  => "Recruit{$faction}",
            'email'                 => $email,
            'password'              => 'Sup3rs3cret!',
            'password_confirmation' => 'Sup3rs3cret!',
            'faction'               => $faction,
        ]);
        expect(User::where('email', $email)->value('faction'))->toBe($faction);
    }
});
