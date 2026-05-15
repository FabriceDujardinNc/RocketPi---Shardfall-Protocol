<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\OperatorSkin;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->op = makeOperator('legendary');
});

it('blocks non-admins from viewing assets page', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)
        ->get("/admin/operators/{$this->op->slug}/assets")
        ->assertForbidden();
});

it('admin sees the assets page with operator skins and accessories loaded', function () {
    $admin  = makeUser(['role' => User::ROLE_ADMIN]);
    $skin   = OperatorSkin::factory()->create(['operator_id' => $this->op->id]);
    $helmet = Accessory::factory()->slot('head')->create();
    $this->op->accessories()->attach($helmet->id, ['is_default' => true]);

    $this->actingAs($admin)
        ->get("/admin/operators/{$this->op->slug}/assets")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Operators/Assets')
            ->where('operator.slug', $this->op->slug)
            ->has('skins', 1)
            ->where('skins.0.id', $skin->id)
            ->has('accessories', 1)
            ->where('accessories.0.is_default', true)
        );
});

it('triggers a base mesh generation and dispatches the polling job', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post("/admin/operators/{$this->op->slug}/assets/generate", [
            'entity_type' => 'operator',
            'entity_slug' => $this->op->slug,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($this->op->fresh()->base_generation_status)->toBe('queued');
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('returns to the page with an error when retrying a ready entity without force', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_model_url'         => 'models/operators/op-test/base.glb',
    ]);

    $this->actingAs($admin)
        ->post("/admin/operators/{$this->op->slug}/assets/generate", [
            'entity_type' => 'operator',
            'entity_slug' => $this->op->slug,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->op->fresh()->base_generation_status)->toBe('ready');
});

it('regenerates a ready entity when force=true', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_model_url'         => 'models/operators/op-test/base.glb',
    ]);

    $this->actingAs($admin)
        ->post("/admin/operators/{$this->op->slug}/assets/generate", [
            'entity_type' => 'operator',
            'entity_slug' => $this->op->slug,
            'force'       => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($this->op->fresh()->base_generation_status)->toBe('queued');
});

it('validates entity_type', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post("/admin/operators/{$this->op->slug}/assets/generate", [
            'entity_type' => 'spaceship',
            'entity_slug' => $this->op->slug,
        ])
        ->assertSessionHasErrors('entity_type');
});
