<?php

use App\Models\Cosmetic;
use App\Models\Operator;
use App\Models\User;

function adminUserC(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/cosmetics')->assertForbidden();
});

it('creates a title cosmetic', function () {
    $this->actingAs(adminUserC())
        ->post('/admin/cosmetics', [
            'slug'        => 'title_apex_2026',
            'name'        => 'Apex 2026',
            'type'        => 'title',
            'rarity'      => 'legendary',
            'operator_id' => null,
            'is_active'   => true,
        ])
        ->assertRedirect();

    expect(Cosmetic::where('slug', 'title_apex_2026')->first())->not->toBeNull()->type->toBe('title');
});

it('requires operator for skins', function () {
    $this->actingAs(adminUserC())
        ->post('/admin/cosmetics', [
            'slug'   => 'skin_orphan',
            'name'   => 'Orphan',
            'type'   => 'skin',
            'rarity' => 'epic',
            'operator_id' => null,
        ])
        ->assertSessionHasErrors(['operator_id']);
});

it('accepts a skin tied to an operator', function () {
    $op = Operator::create([
        'name' => 'Vex', 'codename' => 'VX-01',
        'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'stat_hp' => 100, 'stat_damage' => 100, 'stat_mobility' => 100,
    ]);

    $this->actingAs(adminUserC())
        ->post('/admin/cosmetics', [
            'slug'   => 'skin_vex_neon',
            'name'   => 'Vex — Neon',
            'type'   => 'skin',
            'rarity' => 'epic',
            'operator_id' => $op->id,
        ])
        ->assertRedirect();

    expect(Cosmetic::where('slug', 'skin_vex_neon')->first())
        ->not->toBeNull()
        ->operator_id->toBe($op->id);
});

it('rejects invalid slug format', function () {
    $this->actingAs(adminUserC())
        ->post('/admin/cosmetics', [
            'slug'   => 'BadSlug WithSpaces',
            'name'   => 'X',
            'type'   => 'title',
            'rarity' => 'rare',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('updates and destroys a cosmetic by slug', function () {
    $c = Cosmetic::create([
        'slug' => 'border_legend', 'name' => 'Legend Border',
        'type' => 'border', 'rarity' => 'legendary', 'is_active' => true,
    ]);

    $this->actingAs(adminUserC())
        ->put("/admin/cosmetics/{$c->slug}", [
            'slug'   => 'border_legend',
            'name'   => 'Legend Border v2',
            'type'   => 'border',
            'rarity' => 'epic',
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($c->fresh())->name->toBe('Legend Border v2')->rarity->toBe('epic')->is_active->toBeFalse();

    $this->actingAs(adminUserC())->delete("/admin/cosmetics/{$c->slug}")->assertRedirect();
    expect(Cosmetic::find($c->id))->toBeNull();
});
