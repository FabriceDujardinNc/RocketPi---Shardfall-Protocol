<?php

use App\Models\Banner;
use App\Models\Operator;
use App\Models\User;

function adminUserBanner(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

function makeBannerRow(array $attrs = []): Banner
{
    return Banner::create(array_merge([
        'name'            => 'Test Banner',
        'type'            => 'permanent',
        'rate_legendary'  => 0.02,
        'rate_epic'       => 0.08,
        'rate_rare'       => 0.30,
        'rate_common'     => 0.60,
        'pity_legendary'  => 80,
        'soft_pity_start' => 60,
        'pity_epic'       => 10,
        'is_active'       => false,
    ], $attrs));
}

it('blocks non-admins from listing banners', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)->get('/admin/banners')->assertForbidden();
});

it('admin sees the list', function () {
    makeBannerRow();
    $this->actingAs(adminUserBanner())
        ->get('/admin/banners')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Banners/Index')->has('banners.data', 1));
});

it('creates a banner with valid rates that sum to 1', function () {
    $this->actingAs(adminUserBanner())
        ->post('/admin/banners', [
            'name'            => 'Apex Banner',
            'type'            => 'event',
            'rate_legendary'  => 0.04,
            'rate_epic'       => 0.10,
            'rate_rare'       => 0.30,
            'rate_common'     => 0.56,
            'pity_legendary'  => 80,
            'soft_pity_start' => 60,
            'pity_epic'       => 10,
            'is_active'       => true,
        ])
        ->assertRedirect();

    expect(Banner::where('name', 'Apex Banner')->exists())->toBeTrue();
});

it('rejects when rate sum is not 1', function () {
    $this->actingAs(adminUserBanner())
        ->post('/admin/banners', [
            'name'            => 'Bad', 'type' => 'event',
            'rate_legendary'  => 0.5, 'rate_epic' => 0.5, 'rate_rare' => 0.5, 'rate_common' => 0.5,
            'pity_legendary'  => 80, 'soft_pity_start' => 60, 'pity_epic' => 10,
        ])
        ->assertSessionHasErrors(['rate_legendary']);
});

it('rejects when soft_pity_start exceeds pity_legendary', function () {
    $this->actingAs(adminUserBanner())
        ->post('/admin/banners', [
            'name'            => 'Bad', 'type' => 'event',
            'rate_legendary'  => 0.02, 'rate_epic' => 0.08, 'rate_rare' => 0.30, 'rate_common' => 0.60,
            'pity_legendary'  => 50, 'soft_pity_start' => 100, 'pity_epic' => 10,
        ])
        ->assertSessionHasErrors(['soft_pity_start']);
});

it('rejects unknown rate_up codename', function () {
    $this->actingAs(adminUserBanner())
        ->post('/admin/banners', [
            'name'              => 'Bad', 'type' => 'event',
            'rate_legendary'    => 0.02, 'rate_epic' => 0.08, 'rate_rare' => 0.30, 'rate_common' => 0.60,
            'pity_legendary'    => 80, 'soft_pity_start' => 60, 'pity_epic' => 10,
            'rate_up_operators' => ['UNKNOWN-99'],
        ])
        ->assertSessionHasErrors(['rate_up_operators.0']);
});

it('updates a banner', function () {
    $b = makeBannerRow();
    $this->actingAs(adminUserBanner())
        ->put("/admin/banners/{$b->id}", [
            'name'            => 'Updated',
            'type'            => 'faction',
            'rate_legendary'  => 0.05,
            'rate_epic'       => 0.15,
            'rate_rare'       => 0.30,
            'rate_common'     => 0.50,
            'pity_legendary'  => 100,
            'soft_pity_start' => 75,
            'pity_epic'       => 12,
            'is_active'       => true,
        ])
        ->assertRedirect();

    expect($b->fresh())
        ->name->toBe('Updated')
        ->type->toBe('faction')
        ->is_active->toBeTrue();
});

it('toggles activate', function () {
    $b = makeBannerRow(['is_active' => false]);
    $this->actingAs(adminUserBanner())->post("/admin/banners/{$b->id}/activate")->assertRedirect();
    expect($b->fresh()->is_active)->toBeTrue();
    $this->actingAs(adminUserBanner())->post("/admin/banners/{$b->id}/activate")->assertRedirect();
    expect($b->fresh()->is_active)->toBeFalse();
});

it('soft-deletes and restores a banner', function () {
    $b = makeBannerRow();
    $this->actingAs(adminUserBanner())->delete("/admin/banners/{$b->id}")->assertRedirect();
    expect(Banner::find($b->id))->toBeNull();

    $this->actingAs(adminUserBanner())->post("/admin/banners/{$b->id}/restore")->assertRedirect();
    expect(Banner::find($b->id))->not->toBeNull();
});

it('accepts a valid rate_up codename', function () {
    Operator::create([
        'name' => 'Vex', 'codename' => 'VX-01', 'faction' => 'ORBIT', 'role' => 'sniper', 'rarity' => 'legendary',
        'stat_hp' => 100, 'stat_damage' => 100, 'stat_mobility' => 100,
    ]);

    $this->actingAs(adminUserBanner())
        ->post('/admin/banners', [
            'name'              => 'OK', 'type' => 'event',
            'rate_legendary'    => 0.02, 'rate_epic' => 0.08, 'rate_rare' => 0.30, 'rate_common' => 0.60,
            'pity_legendary'    => 80, 'soft_pity_start' => 60, 'pity_epic' => 10,
            'rate_up_operators' => ['VX-01'],
        ])
        ->assertRedirect();

    expect(Banner::where('name', 'OK')->first()->rate_up_operators)->toBe(['VX-01']);
});
