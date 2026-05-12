<?php

use App\Models\Event;

it('shows the events page with active events grouped by phase', function () {
    $u = makeUser();

    Event::create([
        'name' => 'Apex Cup', 'type' => 'pvp_mode',
        'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(),
        'is_active' => true,
    ]);
    Event::create([
        'name' => 'Story Wraith', 'type' => 'story',
        'starts_at' => now()->addDay(), 'ends_at' => now()->addWeeks(2),
        'is_active' => true,
    ]);
    Event::create([
        'name' => 'Old Banner', 'type' => 'limited_banner',
        'starts_at' => now()->subWeeks(2), 'ends_at' => now()->subDays(3),
        'is_active' => true,
    ]);

    $this->actingAs($u)
        ->get('/events')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Player/Events')
            ->has('events', 3)
            ->where('counts.current', 1)
            ->where('counts.scheduled', 1)
            ->where('counts.expired', 1)
        );
});

it('hides inactive events', function () {
    $u = makeUser();
    Event::create([
        'name' => 'Hidden', 'type' => 'story',
        'starts_at' => now()->subDay(), 'ends_at' => now()->addWeek(),
        'is_active' => false,
    ]);

    $this->actingAs($u)
        ->get('/events')
        ->assertInertia(fn ($p) => $p->has('events', 0));
});

it('requires auth', function () {
    $this->get('/events')->assertRedirect('/login');
});
