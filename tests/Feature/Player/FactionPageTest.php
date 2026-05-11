<?php

use App\Models\Faction;
use App\Models\Operator;
use App\Models\PlayerOperator;
use App\Models\User;

function makeFactionRow(string $slug = 'ORBIT', int $hue = 220): Faction
{
    return Faction::create([
        'slug'      => $slug,
        'name'      => $slug,
        'tagline'   => 'Test',
        'lore'      => 'Lore.',
        'color_hue' => $hue,
    ]);
}

it('lists factions with collection ownership counts', function () {
    $user = makeUser();
    makeFactionRow('ORBIT');
    makeFactionRow('FERRO', 32);

    $orbit1 = makeOperator('legendary', 'ORBIT');
    $orbit2 = makeOperator('rare', 'ORBIT');
    $ferro1 = makeOperator('common', 'FERRO');

    PlayerOperator::create(['user_id' => $user->id, 'operator_id' => $orbit1->id, 'duplicate_count' => 0]);

    $this->actingAs($user)
        ->get('/factions')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/Factions/Index')
            ->has('factions', 2)
            ->where('factions.0.slug', 'FERRO')
            ->where('factions.0.operators_owned', 0)
            ->where('factions.1.slug', 'ORBIT')
            ->where('factions.1.operators_owned', 1));
});

it('shows a faction page with operators flagged owned/not owned', function () {
    $user = makeUser();
    makeFactionRow('VEIL', 290);
    $owned = makeOperator('epic', 'VEIL');
    $locked = makeOperator('rare', 'VEIL');

    PlayerOperator::create(['user_id' => $user->id, 'operator_id' => $owned->id, 'duplicate_count' => 0]);

    $this->actingAs($user)
        ->get('/factions/VEIL')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/Factions/Show')
            ->where('stats.total', 2)
            ->where('stats.owned', 1)
            ->has('byRarity.epic.0', fn ($e) => $e->where('owned', true)->etc())
            ->has('byRarity.rare.0', fn ($e) => $e->where('owned', false)->etc()));
});

it('redirects unauthenticated visits', function () {
    $this->get('/factions')->assertRedirect('/login');
    $this->get('/factions/ORBIT')->assertRedirect('/login');
});
