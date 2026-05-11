<?php

use App\Models\Mission;
use App\Models\User;

function adminUserMission(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

function makeMissionRow(array $attrs = []): Mission
{
    return Mission::create(array_merge([
        'title'            => 'Test Mission',
        'description'      => 'desc',
        'type'             => 'daily',
        'objective_type'   => 'pull',
        'objective_target' => 5,
        'rewards'          => [['type' => 'shards', 'amount' => 50]],
        'xp_reward'        => 100,
        'is_active'        => true,
    ], $attrs));
}

it('blocks non-admins', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)->get('/admin/missions')->assertForbidden();
});

it('lists missions', function () {
    makeMissionRow();
    $this->actingAs(adminUserMission())
        ->get('/admin/missions')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Missions/Index')->has('missions.data', 1));
});

it('creates a mission', function () {
    $this->actingAs(adminUserMission())
        ->post('/admin/missions', [
            'title'            => 'Pull 10 times',
            'type'             => 'daily',
            'objective_type'   => 'pull',
            'objective_target' => 10,
            'rewards'          => [['type' => 'shards', 'amount' => 100]],
            'xp_reward'        => 50,
            'is_active'        => true,
        ])
        ->assertRedirect();

    expect(Mission::where('title', 'Pull 10 times')->first())
        ->not->toBeNull()
        ->objective_target->toBe(10)
        ->rewards->toBe([['type' => 'shards', 'amount' => 100]]);
});

it('rejects empty rewards', function () {
    $this->actingAs(adminUserMission())
        ->post('/admin/missions', [
            'title'            => 'Bad',
            'type'             => 'daily',
            'objective_type'   => 'pull',
            'objective_target' => 5,
            'rewards'          => [],
            'is_active'        => true,
        ])
        ->assertSessionHasErrors(['rewards']);
});

it('rejects invalid objective_type', function () {
    $this->actingAs(adminUserMission())
        ->post('/admin/missions', [
            'title'            => 'Bad',
            'type'             => 'daily',
            'objective_type'   => 'invalid_obj',
            'objective_target' => 5,
            'rewards'          => [['type' => 'shards', 'amount' => 50]],
            'is_active'        => true,
        ])
        ->assertSessionHasErrors(['objective_type']);
});

it('updates a mission', function () {
    $m = makeMissionRow();
    $this->actingAs(adminUserMission())
        ->put("/admin/missions/{$m->id}", [
            'title'            => 'Updated',
            'type'             => 'weekly',
            'objective_type'   => 'pvp_win',
            'objective_target' => 25,
            'rewards'          => [['type' => 'tickets_premium', 'amount' => 1]],
            'xp_reward'        => 300,
            'is_active'        => false,
        ])
        ->assertRedirect();

    expect($m->fresh())
        ->title->toBe('Updated')
        ->type->toBe('weekly')
        ->objective_target->toBe(25)
        ->is_active->toBeFalse();
});

it('soft-deletes and restores a mission', function () {
    $m = makeMissionRow();
    $this->actingAs(adminUserMission())->delete("/admin/missions/{$m->id}")->assertRedirect();
    expect(Mission::find($m->id))->toBeNull();

    $this->actingAs(adminUserMission())->post("/admin/missions/{$m->id}/restore")->assertRedirect();
    expect(Mission::find($m->id))->not->toBeNull();
});
