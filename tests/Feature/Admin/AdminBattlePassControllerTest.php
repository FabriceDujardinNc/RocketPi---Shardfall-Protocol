<?php

use App\Models\BattlePass;
use App\Models\BattlePassTier;
use App\Models\User;

function adminUserBP(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

function makeBattlePass(array $attrs = []): BattlePass
{
    return BattlePass::create(array_merge([
        'name'                  => 'Saison test',
        'season_number'         => 1,
        'total_tiers'           => 5,
        'premium_price_shards'  => 1000,
        'premium_price_tickets' => 5,
        'starts_at'             => now()->subDay(),
        'ends_at'               => now()->addWeek(),
        'is_active'             => true,
    ], $attrs));
}

it('blocks non-admins', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)->get('/admin/battle-passes')->assertForbidden();
});

it('creates a season + seeds default tiers', function () {
    $this->actingAs(adminUserBP())
        ->post('/admin/battle-passes', [
            'name'                 => 'Saison 1 — Test',
            'season_number'        => 1,
            'total_tiers'          => 10,
            'premium_price_shards'  => 1000,
            'premium_price_tickets' => 5,
            'starts_at'            => now()->addDay()->toDateTimeString(),
            'ends_at'              => now()->addWeeks(8)->toDateTimeString(),
            'is_active'            => false,
        ])
        ->assertRedirect();

    $bp = BattlePass::where('name', 'Saison 1 — Test')->first();
    expect($bp)->not->toBeNull();
    expect(BattlePassTier::where('battle_pass_id', $bp->id)->count())->toBe(10);
    // Milestones par défaut sur 5/10/25/50 mais ici on a 10 paliers → tier 5 et 10 sont milestones
    expect(BattlePassTier::where('battle_pass_id', $bp->id)->where('is_milestone', true)->pluck('tier_number')->toArray())
        ->toBe([5, 10]);
});

it('rejects an overlapping period on create', function () {
    makeBattlePass([
        'starts_at' => now()->subDay(),
        'ends_at'   => now()->addWeek(),
    ]);

    $this->actingAs(adminUserBP())
        ->post('/admin/battle-passes', [
            'name'                 => 'Conflicting',
            'season_number'        => 2,
            'total_tiers'          => 5,
            'premium_price_shards'  => 1000,
            'premium_price_tickets' => 5,
            'starts_at'            => now()->toDateTimeString(),       // chevauche
            'ends_at'              => now()->addMonth()->toDateTimeString(),
            'is_active'            => false,
        ])
        ->assertSessionHasErrors(['starts_at']);

    expect(BattlePass::where('name', 'Conflicting')->exists())->toBeFalse();
});

it('accepts a period right after an existing season (no overlap)', function () {
    makeBattlePass([
        'starts_at' => now()->subWeek(),
        'ends_at'   => now()->subDay(),
    ]);

    $this->actingAs(adminUserBP())
        ->post('/admin/battle-passes', [
            'name'                 => 'Next season',
            'season_number'        => 2,
            'total_tiers'          => 5,
            'premium_price_shards'  => 1000,
            'premium_price_tickets' => 5,
            'starts_at'            => now()->toDateTimeString(),
            'ends_at'              => now()->addWeek()->toDateTimeString(),
            'is_active'            => false,
        ])
        ->assertRedirect();
});

it('allows update on the same season without false overlap', function () {
    $bp = makeBattlePass([
        'starts_at' => now()->subDay(),
        'ends_at'   => now()->addWeek(),
    ]);

    // Update dates en gardant le chevauchement avec elle-même → doit passer
    $this->actingAs(adminUserBP())
        ->put("/admin/battle-passes/{$bp->slug}", [
            'name'                 => 'Renamed',
            'season_number'        => $bp->season_number,
            'total_tiers'          => $bp->total_tiers,
            'premium_price_shards'  => $bp->premium_price_shards,
            'premium_price_tickets' => $bp->premium_price_tickets,
            'starts_at'             => now()->toDateTimeString(),
            'ends_at'               => now()->addWeeks(2)->toDateTimeString(),
            'is_active'            => true,
        ])
        ->assertRedirect();

    expect($bp->fresh()->name)->toBe('Renamed');
});

it('rejects update if it would overlap another season', function () {
    makeBattlePass(['season_number' => 1, 'starts_at' => now()->subMonth(), 'ends_at' => now()->subWeek()]);
    $bp2 = makeBattlePass(['season_number' => 2, 'starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth()]);

    // Tenter de déplacer bp2 sur la période de bp1
    $this->actingAs(adminUserBP())
        ->put("/admin/battle-passes/{$bp2->slug}", [
            'name'                 => $bp2->name,
            'season_number'        => $bp2->season_number,
            'total_tiers'          => $bp2->total_tiers,
            'premium_price_shards'  => $bp2->premium_price_shards,
            'premium_price_tickets' => $bp2->premium_price_tickets,
            'starts_at'            => now()->subMonth()->toDateTimeString(),
            'ends_at'              => now()->subWeek()->toDateTimeString(),
            'is_active'            => $bp2->is_active,
        ])
        ->assertSessionHasErrors(['starts_at']);
});

it('bulk-updates tiers', function () {
    $bp = makeBattlePass(['total_tiers' => 3]);
    BattlePassTier::create(['battle_pass_id' => $bp->id, 'tier_number' => 1, 'xp_required' => 100, 'free_reward' => null, 'premium_reward' => null, 'is_milestone' => false]);
    BattlePassTier::create(['battle_pass_id' => $bp->id, 'tier_number' => 2, 'xp_required' => 200, 'free_reward' => null, 'premium_reward' => null, 'is_milestone' => false]);

    $this->actingAs(adminUserBP())
        ->put("/admin/battle-passes/{$bp->slug}/tiers", [
            'tiers' => [
                ['tier_number' => 1, 'xp_required' => 150, 'is_milestone' => true,  'free_reward' => [['type' => 'shards', 'amount' => 50]]],
                ['tier_number' => 2, 'xp_required' => 300, 'is_milestone' => false, 'premium_reward' => [['type' => 'tickets_premium', 'amount' => 1]]],
                ['tier_number' => 3, 'xp_required' => 450, 'is_milestone' => false], // tier qui n'existait pas → créé
            ],
        ])
        ->assertRedirect();

    $t1 = BattlePassTier::where('battle_pass_id', $bp->id)->where('tier_number', 1)->first();
    expect($t1->xp_required)->toBe(150);
    expect($t1->is_milestone)->toBeTrue();
    expect($t1->free_reward)->toBe([['type' => 'shards', 'amount' => 50]]);

    $t3 = BattlePassTier::where('battle_pass_id', $bp->id)->where('tier_number', 3)->first();
    expect($t3)->not->toBeNull();
    expect($t3->xp_required)->toBe(450);
});

it('rejects duplicate tier_number in bulk update', function () {
    $bp = makeBattlePass(['total_tiers' => 2]);

    $this->actingAs(adminUserBP())
        ->put("/admin/battle-passes/{$bp->slug}/tiers", [
            'tiers' => [
                ['tier_number' => 1, 'xp_required' => 100],
                ['tier_number' => 1, 'xp_required' => 200],  // doublon
            ],
        ])
        ->assertSessionHasErrors(['tiers']);
});

it('destroys a battle pass and cascades tiers', function () {
    $bp = makeBattlePass();
    BattlePassTier::create(['battle_pass_id' => $bp->id, 'tier_number' => 1, 'xp_required' => 100, 'free_reward' => null, 'premium_reward' => null, 'is_milestone' => false]);

    $this->actingAs(adminUserBP())
        ->delete("/admin/battle-passes/{$bp->slug}")
        ->assertRedirect();

    expect(BattlePass::find($bp->id))->toBeNull();
    expect(BattlePassTier::where('battle_pass_id', $bp->id)->count())->toBe(0);
});

it('lists battle passes with phase tag', function () {
    makeBattlePass(['starts_at' => now()->subWeek(), 'ends_at' => now()->addWeek()]);

    $this->actingAs(adminUserBP())
        ->get('/admin/battle-passes')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/BattlePass/Index')
            ->has('battlePasses', 1)
            ->where('battlePasses.0.phase', 'current'));
});
