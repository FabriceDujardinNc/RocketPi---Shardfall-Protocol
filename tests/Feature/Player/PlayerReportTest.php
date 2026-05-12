<?php

use App\Models\MatchSession;
use App\Models\PlayerReport;

it('creates a report with valid payload', function () {
    $reporter = makeUser();
    $target = makeUser();

    $this->actingAs($reporter)
        ->post('/reports', [
            'reported_id' => $target->id,
            'reason'      => 'toxic',
            'description' => 'Insultes répétées en chat vocal.',
        ])
        ->assertRedirect();

    $r = PlayerReport::where('reporter_id', $reporter->id)->where('reported_id', $target->id)->first();
    expect($r)->not->toBeNull();
    expect($r->reason)->toBe('toxic');
    expect($r->status)->toBe('pending');
});

it('refuses self-report', function () {
    $u = makeUser();
    $this->actingAs($u)
        ->post('/reports', ['reported_id' => $u->id, 'reason' => 'toxic'])
        ->assertSessionHasErrors(['reported_id']);
});

it('refuses duplicate report on same target+session', function () {
    $reporter = makeUser();
    $target = makeUser();
    $session = MatchSession::create([
        'user_id' => $reporter->id, 'session_token' => str_repeat('a', 64),
        'mode' => 'deathmatch', 'rank_type' => 'ranked', 'status' => 'finished',
        'started_at' => now(),
    ]);

    PlayerReport::create([
        'reporter_id' => $reporter->id, 'reported_id' => $target->id,
        'match_session_id' => $session->id, 'reason' => 'cheat',
    ]);

    $this->actingAs($reporter)
        ->post('/reports', [
            'reported_id' => $target->id,
            'match_session_id' => $session->id,
            'reason' => 'cheat',
        ])
        ->assertSessionHasErrors(['report']);
});

it('refuses report on a session the reporter did not participate', function () {
    $reporter = makeUser();
    $other = makeUser();
    $target = makeUser();
    // Session appartient à other, pas au reporter
    $session = MatchSession::create([
        'user_id' => $other->id, 'session_token' => str_repeat('b', 64),
        'mode' => 'deathmatch', 'rank_type' => 'ranked', 'status' => 'finished',
        'started_at' => now(),
    ]);

    $this->actingAs($reporter)
        ->post('/reports', [
            'reported_id' => $target->id,
            'match_session_id' => $session->id,
            'reason' => 'cheat',
        ])
        ->assertSessionHasErrors(['match_session_id']);
});

it('refuses unknown reason', function () {
    $this->actingAs(makeUser())
        ->post('/reports', ['reported_id' => makeUser()->id, 'reason' => 'racisme'])
        ->assertSessionHasErrors(['reason']);
});

it('accepts report without session (general behavior report)', function () {
    $reporter = makeUser();
    $target = makeUser();

    $this->actingAs($reporter)
        ->post('/reports', [
            'reported_id' => $target->id,
            'reason' => 'smurf',
        ])
        ->assertRedirect();

    expect(PlayerReport::where('reporter_id', $reporter->id)->count())->toBe(1);
});
