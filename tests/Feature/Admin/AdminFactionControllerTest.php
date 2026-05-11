<?php

use App\Models\Faction;
use App\Models\Operator;
use App\Models\User;

function adminUserF(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

function makeFaction(array $attrs = []): Faction
{
    return Faction::create(array_merge([
        'slug'         => 'ORBIT',
        'name'         => 'ORBIT',
        'tagline'      => 'Ingénieurs orbitaux',
        'lore'         => 'Lore initial.',
        'color_hue'    => 220,
        'accent_class' => 'shard-cyan',
    ], $attrs));
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/factions')->assertForbidden();
});

it('lists factions with operator counts', function () {
    makeFaction(['slug' => 'ORBIT']);
    makeFaction(['slug' => 'FERRO', 'name' => 'FERRO', 'color_hue' => 32]);
    makeOperator('legendary', 'ORBIT');
    makeOperator('rare', 'ORBIT');
    makeOperator('common', 'FERRO');

    $this->actingAs(adminUserF())
        ->get('/admin/factions')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Factions/Index')->has('factions', 2));
});

it('shows a faction with its operators grouped by rarity', function () {
    makeFaction(['slug' => 'VEIL', 'name' => 'VEIL', 'color_hue' => 290]);
    makeOperator('legendary', 'VEIL');
    makeOperator('epic', 'VEIL');
    makeOperator('common', 'VEIL');

    $this->actingAs(adminUserF())
        ->get('/admin/factions/VEIL')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Factions/Show')
            ->where('stats.total', 3)
            ->has('byRarity.legendary', 1)
            ->has('byRarity.epic', 1)
            ->has('byRarity.common', 1));
});

it('updates the editorial fields of a faction', function () {
    $f = makeFaction(['slug' => 'ORBIT']);

    $this->actingAs(adminUserF())
        ->put('/admin/factions/ORBIT', [
            'name'             => 'ORBIT — Étoiles',
            'tagline'          => 'Nouveau tagline',
            'lore'             => 'Réécriture du lore.',
            'color_hue'        => 200,
            'accent_class'     => 'shard-blue',
            'banner_image_url' => null,
            'icon_url'         => null,
        ])
        ->assertRedirect();

    expect($f->fresh())
        ->name->toBe('ORBIT — Étoiles')
        ->tagline->toBe('Nouveau tagline')
        ->color_hue->toBe(200);
});

it('rejects invalid color_hue', function () {
    makeFaction(['slug' => 'ORBIT']);
    $this->actingAs(adminUserF())
        ->put('/admin/factions/ORBIT', [
            'name'      => 'Bad',
            'color_hue' => 999,
        ])
        ->assertSessionHasErrors(['color_hue']);
});

it('rejects invalid url for banner_image_url', function () {
    makeFaction(['slug' => 'ORBIT']);
    $this->actingAs(adminUserF())
        ->put('/admin/factions/ORBIT', [
            'name'             => 'X',
            'color_hue'        => 220,
            'banner_image_url' => 'pas-une-url',
        ])
        ->assertSessionHasErrors(['banner_image_url']);
});
