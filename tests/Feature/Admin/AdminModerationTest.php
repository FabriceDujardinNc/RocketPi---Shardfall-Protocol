<?php

use App\Models\MatchSession;
use App\Models\PlayerReport;
use App\Models\User;

function adminMod(): User
{
    return makeUser(['role' => User::ROLE_ADMIN]);
}

it('blocks non-admin from moderation index', function () {
    $this->actingAs(makeUser())->get('/admin/moderation')->assertForbidden();
});

it('lists pending reports by default', function () {
    $reporter = makeUser();
    $target = makeUser();
    PlayerReport::create([
        'reporter_id' => $reporter->id, 'reported_id' => $target->id,
        'reason' => 'cheat', 'status' => 'pending',
    ]);

    $this->actingAs(adminMod())
        ->get('/admin/moderation')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Moderation/Index')
            ->has('reports.data', 1)
            ->where('filters.status', 'pending')
        );
});

it('filters by status and reason', function () {
    $reporter = makeUser();
    $target = makeUser();
    PlayerReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'cheat', 'status' => 'pending']);
    PlayerReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'toxic', 'status' => 'dismissed']);

    $this->actingAs(adminMod())
        ->get('/admin/moderation?status=dismissed&reason=toxic')
        ->assertInertia(fn ($p) => $p->has('reports.data', 1));
});

it('dismisses a pending report', function () {
    $r = PlayerReport::create([
        'reporter_id' => makeUser()->id,
        'reported_id' => makeUser()->id,
        'reason' => 'cheat', 'status' => 'pending',
    ]);

    $admin = adminMod();
    $this->actingAs($admin)
        ->post("/admin/moderation/{$r->id}/dismiss", ['notes' => 'Pas convaincant.'])
        ->assertRedirect();

    expect($r->fresh()->status)->toBe('dismissed');
    expect($r->fresh()->reviewed_by)->toBe($admin->id);
});

it('sanctions a report by banning the reported user', function () {
    $target = makeUser();
    $r = PlayerReport::create([
        'reporter_id' => makeUser()->id, 'reported_id' => $target->id,
        'reason' => 'cheat', 'status' => 'pending',
    ]);

    $this->actingAs(adminMod())
        ->post("/admin/moderation/{$r->id}/sanction", ['ban_reason' => 'Triche confirmée'])
        ->assertRedirect();

    expect($r->fresh()->status)->toBe('sanctioned');
    expect($target->fresh()->is_banned)->toBeTrue();
    expect($target->fresh()->ban_reason)->toContain('Triche confirmée');
    expect($target->fresh()->ban_reason)->toContain('report #'.$r->id);
});

it('cannot ban an admin via report', function () {
    $admin = adminMod();
    $r = PlayerReport::create([
        'reporter_id' => makeUser()->id, 'reported_id' => $admin->id,
        'reason' => 'toxic', 'status' => 'pending',
    ]);

    $this->actingAs(adminMod())
        ->post("/admin/moderation/{$r->id}/sanction", ['ban_reason' => 'X']);

    // Report quand même marqué sanctioned mais admin pas banni
    expect($admin->fresh()->is_banned)->toBeFalse();
});

it('rejects sanction if report already closed', function () {
    $r = PlayerReport::create([
        'reporter_id' => makeUser()->id, 'reported_id' => makeUser()->id,
        'reason' => 'cheat', 'status' => 'dismissed',
    ]);

    $this->actingAs(adminMod())
        ->post("/admin/moderation/{$r->id}/sanction", ['ban_reason' => 'X'])
        ->assertSessionHasErrors(['report']);
});

it('marks as reviewed without trancher', function () {
    $r = PlayerReport::create([
        'reporter_id' => makeUser()->id, 'reported_id' => makeUser()->id,
        'reason' => 'afk', 'status' => 'pending',
    ]);

    $this->actingAs(adminMod())
        ->post("/admin/moderation/{$r->id}/reviewed")
        ->assertRedirect();

    expect($r->fresh()->status)->toBe('reviewed');
});
