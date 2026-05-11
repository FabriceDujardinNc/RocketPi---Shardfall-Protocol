<?php

use App\Models\DailyLoginReward;
use App\Models\User;
use App\Services\DailyLoginService;

function adminUserDL(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/daily-login-rewards')->assertForbidden();
});

it('creates a daily login reward', function () {
    $this->actingAs(adminUserDL())
        ->post('/admin/daily-login-rewards', [
            'day_number'   => 14,
            'label'        => 'Bonus mi-mois',
            'is_milestone' => true,
            'rewards'      => [['type' => 'shards', 'amount' => 75]],
        ])
        ->assertRedirect();

    $row = DailyLoginReward::where('day_number', 14)->first();
    expect($row)->not->toBeNull();
    expect($row->rewards)->toBe([['type' => 'shards', 'amount' => 75]]);
});

it('rejects duplicate day_number', function () {
    DailyLoginReward::create(['day_number' => 5, 'rewards' => [['type' => 'shards', 'amount' => 10]], 'is_milestone' => false]);

    $this->actingAs(adminUserDL())
        ->post('/admin/daily-login-rewards', [
            'day_number' => 5,
            'rewards'    => [['type' => 'shards', 'amount' => 99]],
        ])
        ->assertSessionHasErrors(['day_number']);
});

it('updates and deletes a reward', function () {
    $r = DailyLoginReward::create(['day_number' => 3, 'rewards' => [['type' => 'shards', 'amount' => 10]], 'is_milestone' => false]);

    $this->actingAs(adminUserDL())
        ->put("/admin/daily-login-rewards/{$r->id}", [
            'day_number'   => 3,
            'is_milestone' => true,
            'rewards'      => [['type' => 'credits', 'amount' => 500]],
        ])
        ->assertRedirect();

    expect($r->fresh()->is_milestone)->toBeTrue();
    expect($r->fresh()->rewards)->toBe([['type' => 'credits', 'amount' => 500]]);

    $this->actingAs(adminUserDL())->delete("/admin/daily-login-rewards/{$r->id}")->assertRedirect();
    expect(DailyLoginReward::find($r->id))->toBeNull();
});

it('DailyLoginService::rewardForDay reads from DB before falling back to constant', function () {
    DailyLoginReward::create([
        'day_number'   => 1,
        'rewards'      => [['type' => 'shards', 'amount' => 999]],
        'is_milestone' => true,
    ]);

    expect(app(DailyLoginService::class)->rewardForDay(1))
        ->toBe([['type' => 'shards', 'amount' => 999]]);

    // Jour absent → fallback DEFAULT_REWARD (100 credits)
    expect(app(DailyLoginService::class)->rewardForDay(42))
        ->toBe(DailyLoginService::DEFAULT_REWARD);
});
