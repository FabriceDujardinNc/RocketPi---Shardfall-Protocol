<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->op = makeOperator('legendary');
});

it('blocks non-admins from listing accessories', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)
        ->get('/admin/accessories')
        ->assertForbidden();
});

it('admin sees the accessories index with linked operators preloaded', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $accessory = Accessory::factory()->slot('head')->create(['name' => 'Visière ORBIT']);
    $this->op->accessories()->attach($accessory->id, ['is_default' => true]);

    $this->actingAs($admin)
        ->get('/admin/accessories')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Accessories/Index')
            ->has('accessories.data', 1)
            ->where('accessories.data.0.id', $accessory->id)
            ->has('accessories.data.0.operators', 1)
        );
});

it('admin filters accessories by slot and rarity', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    Accessory::factory()->slot('head')->create(['rarity' => 'legendary', 'name' => 'A']);
    Accessory::factory()->slot('back')->create(['rarity' => 'common',    'name' => 'B']);

    $this->actingAs($admin)
        ->get('/admin/accessories?slot=head&rarity=legendary')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('accessories.data', 1)
            ->where('accessories.data.0.slot', 'head'));
});

it('creates an accessory and attaches operators with a default', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $other = makeOperator('common');

    $this->actingAs($admin)
        ->post('/admin/accessories', [
            'name'        => 'Casque Test',
            'slot'        => 'head',
            'rarity'      => 'rare',
            'socket_name' => 'Head_Top',
            'is_active'   => true,
            'operator_ids'        => [$this->op->id, $other->id],
            'default_operator_id' => $this->op->id,
        ])
        ->assertRedirect();

    $accessory = Accessory::where('name', 'Casque Test')->firstOrFail();
    expect($accessory->operators)->toHaveCount(2);
    expect($accessory->operators->firstWhere('id', $this->op->id)->pivot->is_default)->toBeTrue();
    expect($accessory->operators->firstWhere('id', $other->id)->pivot->is_default)->toBeFalse();
});

it('updates an accessory and re-syncs operators', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $accessory = Accessory::factory()->slot('head')->create(['name' => 'Old']);
    $this->op->accessories()->attach($accessory->id, ['is_default' => true]);

    $other = makeOperator('common');

    $this->actingAs($admin)
        ->put("/admin/accessories/{$accessory->slug}", [
            'name'        => 'Renamed',
            'slot'        => 'face',
            'rarity'      => 'epic',
            'socket_name' => 'Face_Front',
            'is_active'   => true,
            'operator_ids'        => [$other->id],
            'default_operator_id' => $other->id,
        ])
        ->assertRedirect();

    $accessory->refresh()->load('operators');
    expect($accessory->name)->toBe('Renamed');
    expect($accessory->slot)->toBe('face');
    expect($accessory->operators)->toHaveCount(1);
    expect($accessory->operators->first()->id)->toBe($other->id);
    expect($accessory->operators->first()->pivot->is_default)->toBeTrue();
});

it('deletes an accessory', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $accessory = Accessory::factory()->slot('head')->create();

    $this->actingAs($admin)
        ->delete("/admin/accessories/{$accessory->slug}")
        ->assertRedirect('/admin/accessories');

    expect(Accessory::find($accessory->id))->toBeNull();
});

it('triggers an accessory generation and dispatches the polling job', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $accessory = Accessory::factory()->slot('head')->create();

    $this->actingAs($admin)
        ->post("/admin/accessories/{$accessory->slug}/generate")
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($accessory->fresh()->generation_status)->toBe('queued');
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('returns an error when generating a ready accessory without force', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $accessory = Accessory::factory()->slot('head')->ready()->create();

    $this->actingAs($admin)
        ->post("/admin/accessories/{$accessory->slug}/generate")
        ->assertRedirect()
        ->assertSessionHas('error');
});
