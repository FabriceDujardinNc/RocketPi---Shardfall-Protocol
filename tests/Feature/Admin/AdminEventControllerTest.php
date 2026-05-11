<?php

use App\Models\Event;
use App\Models\User;

function adminUserEv(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admins', function () {
    $u = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($u)->get('/admin/events')->assertForbidden();
});

it('creates an event', function () {
    $this->actingAs(adminUserEv())
        ->post('/admin/events', [
            'name'         => 'Apex Showdown',
            'type'         => 'limited_banner',
            'banner_id'    => null,
            'rewards_pool' => [['type' => 'shards', 'amount' => 500]],
            'starts_at'    => now()->toDateTimeString(),
            'ends_at'      => now()->addWeek()->toDateTimeString(),
            'is_active'    => true,
        ])
        ->assertRedirect();

    expect(Event::where('name', 'Apex Showdown')->first())
        ->not->toBeNull()
        ->type->toBe('limited_banner')
        ->is_active->toBeTrue();
});

it('rejects ends_at before starts_at', function () {
    $this->actingAs(adminUserEv())
        ->post('/admin/events', [
            'name'      => 'Bad',
            'type'      => 'story',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at'   => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['ends_at']);
});

it('rejects invalid type', function () {
    $this->actingAs(adminUserEv())
        ->post('/admin/events', [
            'name'      => 'Bad',
            'type'      => 'made_up_type',
            'starts_at' => now()->toDateTimeString(),
            'ends_at'   => now()->addDay()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['type']);
});

it('updates and destroys an event', function () {
    $e = Event::create([
        'name'      => 'X',
        'type'      => 'pve_mode',
        'starts_at' => now(),
        'ends_at'   => now()->addWeek(),
        'is_active' => false,
    ]);

    $this->actingAs(adminUserEv())
        ->put("/admin/events/{$e->slug}", [
            'name'      => 'X-Updated',
            'type'      => 'pvp_mode',
            'starts_at' => $e->starts_at->toDateTimeString(),
            'ends_at'   => $e->ends_at->toDateTimeString(),
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($e->fresh())->type->toBe('pvp_mode')->is_active->toBeTrue();

    $this->actingAs(adminUserEv())->delete("/admin/events/{$e->slug}")->assertRedirect();
    expect(Event::find($e->id))->toBeNull();
});
