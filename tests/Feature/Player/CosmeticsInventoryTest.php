<?php

use App\Models\Cosmetic;
use App\Models\PlayerCosmetic;
use App\Services\AffinityService;

it('lists owned cosmetics on inventory page', function () {
    $u = makeUser();
    $title = Cosmetic::create(['slug' => 'title_apex_test', 'name' => 'Apex', 'type' => 'title', 'rarity' => 'legendary']);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $title->id, 'source' => 'test']);

    $this->actingAs($u)
        ->get('/cosmetics')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/Cosmetics')
            ->where('totalCount', 1)
            ->has('inventory.0', fn ($i) => $i
                ->where('slug', 'title_apex_test')
                ->where('is_equipped', false)
                ->etc()
            )
        );
});

it('hides cosmetics not owned by the player', function () {
    $u = makeUser();
    $other = makeUser();
    $c = Cosmetic::create(['slug' => 'title_other', 'name' => 'X', 'type' => 'title', 'rarity' => 'rare']);
    PlayerCosmetic::create(['user_id' => $other->id, 'cosmetic_id' => $c->id]);

    $this->actingAs($u)
        ->get('/cosmetics')
        ->assertInertia(fn ($p) => $p->where('totalCount', 0));
});

it('equips a title and unequips any previously equipped title', function () {
    $u = makeUser();
    $t1 = Cosmetic::create(['slug' => 't1', 'name' => 'T1', 'type' => 'title', 'rarity' => 'rare']);
    $t2 = Cosmetic::create(['slug' => 't2', 'name' => 'T2', 'type' => 'title', 'rarity' => 'epic']);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $t1->id, 'is_equipped' => true]);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $t2->id, 'is_equipped' => false]);

    $this->actingAs($u)->post("/cosmetics/{$t2->slug}/equip")->assertRedirect();

    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $t1->id)->value('is_equipped'))->toBeFalsy();
    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $t2->id)->value('is_equipped'))->toBeTruthy();
});

it('allows multiple skins equipped if for different operators', function () {
    $u = makeUser();
    $op1 = makeOperator();
    $op2 = makeOperator();
    $s1 = Cosmetic::create(['slug' => 'sk1', 'name' => 'S1', 'type' => 'skin', 'rarity' => 'rare', 'operator_id' => $op1->id]);
    $s2 = Cosmetic::create(['slug' => 'sk2', 'name' => 'S2', 'type' => 'skin', 'rarity' => 'rare', 'operator_id' => $op2->id]);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $s1->id]);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $s2->id]);

    $this->actingAs($u)->post("/cosmetics/{$s1->slug}/equip");
    $this->actingAs($u)->post("/cosmetics/{$s2->slug}/equip");

    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $s1->id)->value('is_equipped'))->toBeTruthy();
    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $s2->id)->value('is_equipped'))->toBeTruthy();
});

it('replaces equipped skin when equipping another for the same operator', function () {
    $u = makeUser();
    $op = makeOperator();
    $s1 = Cosmetic::create(['slug' => 'a', 'name' => 'A', 'type' => 'skin', 'rarity' => 'rare', 'operator_id' => $op->id]);
    $s2 = Cosmetic::create(['slug' => 'b', 'name' => 'B', 'type' => 'skin', 'rarity' => 'epic', 'operator_id' => $op->id]);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $s1->id, 'is_equipped' => true]);
    PlayerCosmetic::create(['user_id' => $u->id, 'cosmetic_id' => $s2->id, 'is_equipped' => false]);

    $this->actingAs($u)->post("/cosmetics/{$s2->slug}/equip");

    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $s1->id)->value('is_equipped'))->toBeFalsy();
    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $s2->id)->value('is_equipped'))->toBeTruthy();
});

it('refuses to equip a cosmetic the player does not own', function () {
    $u = makeUser();
    $c = Cosmetic::create(['slug' => 'foreign', 'name' => 'X', 'type' => 'title', 'rarity' => 'rare']);

    $this->actingAs($u)->post("/cosmetics/{$c->slug}/equip")->assertSessionHasErrors(['cosmetic']);
});

it('unlocks cosmetics when affinity level reaches unlock threshold', function () {
    $u = makeUser();
    $op = makeOperator('legendary');
    $skin = Cosmetic::create([
        'slug' => 'skin_lvl5', 'name' => 'Lvl5', 'type' => 'skin', 'rarity' => 'epic',
        'operator_id' => $op->id, 'unlock_at_affinity' => 5,
    ]);
    $skin10 = Cosmetic::create([
        'slug' => 'skin_lvl10', 'name' => 'Lvl10', 'type' => 'skin', 'rarity' => 'legendary',
        'operator_id' => $op->id, 'unlock_at_affinity' => 10,
    ]);

    // Pour atteindre level 5 : 100+200+300+400+500 = 1500 XP
    app(AffinityService::class)->award($u, $op, 1500);

    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $skin->id)->exists())->toBeTrue();
    expect(PlayerCosmetic::where('user_id', $u->id)->where('cosmetic_id', $skin10->id)->exists())->toBeFalse();
});

it('does not double-unlock the same cosmetic', function () {
    $u = makeUser();
    $op = makeOperator();
    Cosmetic::create([
        'slug' => 'once', 'name' => 'Once', 'type' => 'skin', 'rarity' => 'rare',
        'operator_id' => $op->id, 'unlock_at_affinity' => 1,
    ]);

    app(AffinityService::class)->award($u, $op, 100);
    app(AffinityService::class)->award($u, $op, 100);

    expect(PlayerCosmetic::where('user_id', $u->id)->count())->toBe(1);
});

it('requires authentication to view inventory', function () {
    $this->get('/cosmetics')->assertRedirect('/login');
});
