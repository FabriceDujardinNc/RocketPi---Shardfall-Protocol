<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\OperatorSkin;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->op = makeOperator('legendary');
});

it('blocks non-admins from listing skins', function () {
    $user = makeUser(['role' => User::ROLE_USER]);
    $this->actingAs($user)
        ->get('/admin/skins')
        ->assertForbidden();
});

it('admin sees the skins index with skin operator preloaded', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $skin  = OperatorSkin::factory()->create(['operator_id' => $this->op->id, 'name' => 'Vex Eclipse']);

    $this->actingAs($admin)
        ->get('/admin/skins')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Skins/Index')
            ->has('skins.data', 1)
            ->where('skins.data.0.id', $skin->id)
            ->where('skins.data.0.operator.codename', $this->op->codename)
        );
});

it('admin filters skins by operator and rarity', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $other = makeOperator('common');
    OperatorSkin::factory()->create(['operator_id' => $this->op->id, 'rarity' => 'legendary', 'name' => 'A']);
    OperatorSkin::factory()->create(['operator_id' => $other->id,   'rarity' => 'common',    'name' => 'B']);

    $this->actingAs($admin)
        ->get("/admin/skins?operator={$this->op->id}&rarity=legendary")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('skins.data', 1)
            ->where('skins.data.0.rarity', 'legendary'));
});

it('creates a skin via store', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post('/admin/skins', [
            'operator_id' => $this->op->id,
            'name'        => 'Vex Solar',
            'rarity'      => 'legendary',
            'palette_json' => [['slot' => 'primary', 'hex' => '#facc15']],
            'is_active'   => true,
            'is_default'  => false,
        ])
        ->assertRedirect();

    expect(OperatorSkin::where('name', 'Vex Solar')->first())
        ->not->toBeNull()
        ->rarity->toBe('legendary')
        ->palette_json->toBe([['slot' => 'primary', 'hex' => '#facc15']]);
});

it('rejects invalid palette hex', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post('/admin/skins', [
            'operator_id' => $this->op->id,
            'name'        => 'Bad',
            'rarity'      => 'rare',
            'palette_json' => [['slot' => 'primary', 'hex' => 'notahex']],
        ])
        ->assertSessionHasErrors('palette_json.0.hex');
});

it('updates a skin via put', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $skin  = OperatorSkin::factory()->create(['operator_id' => $this->op->id, 'name' => 'Old', 'rarity' => 'common']);

    $this->actingAs($admin)
        ->put("/admin/skins/{$skin->slug}", [
            'operator_id' => $this->op->id,
            'name'        => 'Renamed',
            'rarity'      => 'epic',
        ])
        ->assertRedirect();

    expect($skin->fresh())->name->toBe('Renamed')->rarity->toBe('epic');
});

it('deletes a skin', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $skin  = OperatorSkin::factory()->create(['operator_id' => $this->op->id]);

    $this->actingAs($admin)
        ->delete("/admin/skins/{$skin->slug}")
        ->assertRedirect('/admin/skins');

    expect(OperatorSkin::find($skin->id))->toBeNull();
});

it('triggers a skin generation and dispatches the polling job', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $this->op->update(['base_model_url' => 'models/operators/op-test/base.glb']);
    $skin = OperatorSkin::factory()->create(['operator_id' => $this->op->id]);

    $this->actingAs($admin)
        ->post("/admin/skins/{$skin->slug}/generate")
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($skin->fresh()->generation_status)->toBe('queued');
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('returns an error when generating a ready skin without force', function () {
    $admin = makeUser(['role' => User::ROLE_ADMIN]);
    $skin  = OperatorSkin::factory()->ready()->create(['operator_id' => $this->op->id]);

    $this->actingAs($admin)
        ->post("/admin/skins/{$skin->slug}/generate")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($skin->fresh()->generation_status)->toBe('ready');
});
