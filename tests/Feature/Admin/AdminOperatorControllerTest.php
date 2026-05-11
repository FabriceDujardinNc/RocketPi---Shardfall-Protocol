<?php

use App\Models\Operator;
use App\Models\User;

function adminUser(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins from listing operators', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)->get('/admin/operators')->assertForbidden();
});

it('allows admin to list operators', function () {
    makeOperator('legendary', 'ORBIT');
    $this->actingAs(adminUser())
        ->get('/admin/operators')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Operators/Index')->has('operators.data', 1));
});

it('creates an operator with valid payload', function () {
    $this->actingAs(adminUser())
        ->post('/admin/operators', [
            'name'          => 'Phantom',
            'codename'      => 'PH-99',
            'faction'       => 'VEIL',
            'role'          => 'infiltrator',
            'rarity'        => 'epic',
            'stat_hp'       => 220,
            'stat_damage'   => 80,
            'stat_mobility' => 90,
            'abilities'     => [
                ['name' => 'Phase Shift', 'type' => 'active', 'description' => 'Devient intangible 3s.'],
            ],
            'is_available'  => true,
            'is_rate_up'    => false,
            'sort_order'    => 0,
        ])
        ->assertRedirect();

    $op = Operator::where('codename', 'PH-99')->first();
    expect($op)->not->toBeNull();
    expect($op->name)->toBe('Phantom');
    expect($op->abilities)->toHaveCount(1);
    expect($op->abilities[0]['type'])->toBe('active');
});

it('rejects duplicate codename', function () {
    Operator::create([
        'name' => 'Vex', 'codename' => 'VX-01', 'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'stat_hp' => 200, 'stat_damage' => 150, 'stat_mobility' => 60,
    ]);

    $this->actingAs(adminUser())
        ->post('/admin/operators', [
            'name'          => 'Other',
            'codename'      => 'VX-01',
            'faction'       => 'ORBIT',
            'role'          => 'sniper',
            'rarity'        => 'rare',
            'stat_hp'       => 100, 'stat_damage' => 50, 'stat_mobility' => 50,
        ])
        ->assertSessionHasErrors(['codename']);
});

it('rejects out-of-enum faction', function () {
    $this->actingAs(adminUser())
        ->post('/admin/operators', [
            'name'        => 'Bad', 'codename' => 'BD-01',
            'faction'     => 'NOPE',
            'role'        => 'sniper', 'rarity'      => 'rare',
            'stat_hp'     => 100, 'stat_damage' => 50, 'stat_mobility' => 50,
        ])
        ->assertSessionHasErrors(['faction']);
});

it('updates an operator', function () {
    $op = makeOperator('rare', 'ORBIT');

    $this->actingAs(adminUser())
        ->put("/admin/operators/{$op->slug}", [
            'name'          => $op->name,
            'codename'      => $op->codename,
            'faction'       => 'FERRO',
            'role'          => 'tank',
            'rarity'        => 'epic',
            'stat_hp'       => 500,
            'stat_damage'   => 30,
            'stat_mobility' => 20,
            'is_available'  => true,
            'is_rate_up'    => true,
            'sort_order'    => 5,
        ])
        ->assertRedirect();

    expect($op->fresh())
        ->faction->toBe('FERRO')
        ->role->toBe('tank')
        ->rarity->toBe('epic')
        ->stat_hp->toBe(500)
        ->is_rate_up->toBeTrue();
});

it('soft-deletes an operator', function () {
    $op = makeOperator('common', 'FERRO');

    $this->actingAs(adminUser())
        ->delete("/admin/operators/{$op->slug}")
        ->assertRedirect();

    expect(Operator::find($op->id))->toBeNull();
    expect(Operator::withTrashed()->find($op->id)->deleted_at)->not->toBeNull();
});

it('restores a soft-deleted operator', function () {
    $op = makeOperator('common', 'FERRO');
    $op->delete();

    $this->actingAs(adminUser())
        ->post("/admin/operators/{$op->slug}/restore")
        ->assertRedirect();

    expect(Operator::find($op->id))->not->toBeNull();
    expect(Operator::find($op->id)->deleted_at)->toBeNull();
});

it('forbids non-admins from creating', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)
        ->post('/admin/operators', [
            'name' => 'X', 'codename' => 'X-01',
            'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'rare',
            'stat_hp' => 100, 'stat_damage' => 50, 'stat_mobility' => 50,
        ])
        ->assertForbidden();
});
