<?php

use App\Models\LeaderboardSeason;
use App\Models\User;

function adminUserLB(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/leaderboards/create')->assertForbidden();
});

it('creates a season', function () {
    $this->actingAs(adminUserLB())
        ->post('/admin/leaderboards', [
            'name'          => 'Saison 2 — Apex',
            'type'          => 'seasonal',
            'faction'       => null,
            'season_number' => 2,
            'starts_at'     => now()->toDateTimeString(),
            'ends_at'       => now()->addMonths(3)->toDateTimeString(),
            'is_active'     => true,
        ])
        ->assertRedirect();

    $s = LeaderboardSeason::where('name', 'Saison 2 — Apex')->first();
    expect($s)->not->toBeNull();
    expect($s->type)->toBe('seasonal');
    expect($s->slug)->toBe('saison-2-apex');
});

it('requires faction for type=faction', function () {
    $this->actingAs(adminUserLB())
        ->post('/admin/leaderboards', [
            'name'          => 'Bad',
            'type'          => 'faction',
            'faction'       => null,
            'season_number' => 1,
            'starts_at'     => now()->toDateTimeString(),
            'ends_at'       => now()->addWeek()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['faction']);
});

it('rejects ends_at before starts_at', function () {
    $this->actingAs(adminUserLB())
        ->post('/admin/leaderboards', [
            'name'          => 'Bad',
            'type'          => 'weekly',
            'season_number' => 1,
            'starts_at'     => now()->addWeek()->toDateTimeString(),
            'ends_at'       => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['ends_at']);
});

it('updates a season via slug', function () {
    $s = LeaderboardSeason::create([
        'name'          => 'Saison Test',
        'type'          => 'monthly',
        'season_number' => 1,
        'starts_at'     => now(),
        'ends_at'       => now()->addMonth(),
        'is_active'     => false,
    ]);

    $this->actingAs(adminUserLB())
        ->put("/admin/leaderboards/{$s->slug}", [
            'name'          => 'Renommée',
            'type'          => 'monthly',
            'season_number' => 1,
            'starts_at'     => $s->starts_at->toDateTimeString(),
            'ends_at'       => $s->ends_at->toDateTimeString(),
            'is_active'     => true,
        ])
        ->assertRedirect();

    expect($s->fresh())->name->toBe('Renommée')->is_active->toBeTrue();
});

it('destroys a season that has no rewards distributed', function () {
    $s = LeaderboardSeason::create([
        'name'          => 'To delete', 'type' => 'weekly', 'season_number' => 99,
        'starts_at'     => now(),       'ends_at' => now()->addWeek(), 'is_active' => false,
        'rewards_distributed' => false,
    ]);

    $this->actingAs(adminUserLB())->delete("/admin/leaderboards/{$s->slug}")->assertRedirect();
    expect(LeaderboardSeason::find($s->id))->toBeNull();
});

it('refuses to destroy a season with rewards already distributed', function () {
    $s = LeaderboardSeason::create([
        'name' => 'Closed', 'type' => 'weekly', 'season_number' => 50,
        'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay(),
        'is_active' => false, 'rewards_distributed' => true,
    ]);

    $this->actingAs(adminUserLB())->delete("/admin/leaderboards/{$s->slug}")->assertForbidden();
    expect(LeaderboardSeason::find($s->id))->not->toBeNull();
});
