<?php

use App\Models\Faction;
use App\Models\Operator;

beforeEach(function () {
    foreach (['ORBIT', 'FERRO', 'VEIL'] as $slug) {
        Faction::firstOrCreate(['slug' => $slug], [
            'name' => $slug, 'tagline' => "{$slug} tag", 'lore' => "{$slug} lore complet",
            'color_hue' => 100,
        ]);
    }
});

it('serves public lore index without auth', function () {
    $this->get('/lore')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Public/Lore/Index')
            ->has('factions', 3)
            ->where('operatorsCount', 0)
        );
});

it('serves public faction page', function () {
    Operator::create([
        'name' => 'Vex', 'codename' => 'VX-01',
        'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'is_available' => true,
        'stat_hp' => 80, 'stat_damage' => 95, 'stat_mobility' => 70,
    ]);

    $this->get('/lore/factions/ORBIT')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Public/Lore/Faction')
            ->where('faction.slug', 'ORBIT')
            ->has('operators', 1)
        );
});

it('omits unavailable operators from public faction page', function () {
    Operator::create([
        'name' => 'Hidden', 'codename' => 'HD-01',
        'faction' => 'ORBIT', 'role' => 'scout', 'rarity' => 'rare',
        'is_available' => false,
        'stat_hp' => 1, 'stat_damage' => 1, 'stat_mobility' => 1,
    ]);

    $this->get('/lore/factions/ORBIT')
        ->assertInertia(fn ($p) => $p->has('operators', 0));
});

it('serves public operator page', function () {
    $op = Operator::create([
        'name' => 'Vex', 'codename' => 'VX-01',
        'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'is_available' => true,
        'lore' => 'Lore complet de Vex.',
        'stat_hp' => 80, 'stat_damage' => 95, 'stat_mobility' => 70,
    ]);

    $this->get("/lore/operators/{$op->slug}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Public/Lore/Operator')
            ->where('operator.slug', $op->slug)
            ->where('operator.name', 'Vex')
        );
});

it('returns 404 for unavailable operator on public page', function () {
    $op = Operator::create([
        'name' => 'Hidden', 'codename' => 'HD-99',
        'faction' => 'VEIL', 'role' => 'scout', 'rarity' => 'rare',
        'is_available' => false,
        'stat_hp' => 1, 'stat_damage' => 1, 'stat_mobility' => 1,
    ]);

    $this->get("/lore/operators/{$op->slug}")->assertNotFound();
});

it('returns 404 for unknown faction', function () {
    $this->get('/lore/factions/CHAOS')->assertNotFound();
});
