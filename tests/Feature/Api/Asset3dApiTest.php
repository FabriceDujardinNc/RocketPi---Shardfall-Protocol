<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\User;
use App\Models\Weapon;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = makeUser(['role' => User::ROLE_ADMIN]);
});

// ─── Auth & abilities ─────────────────────────────────────────────────

it('rejects asset3d listing without authentication', function () {
    $this->getJson('/api/asset3d/operators')->assertUnauthorized();
});

it('rejects listing when token lacks mcp:read ability', function () {
    Sanctum::actingAs($this->admin, ['unity:*']);

    $this->getJson('/api/asset3d/operators')->assertForbidden();
});

it('allows listing with mcp:read ability', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    makeOperator('legendary');

    $this->getJson('/api/asset3d/operators')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'slug', 'codename', 'base' => ['generation_status']]]]);
});

it('rejects generate trigger when token only has mcp:read', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    $op = makeOperator('legendary');

    $this->postJson('/api/asset3d/generate', [
        'entity_type' => 'operator',
        'slug'        => $op->slug,
    ])->assertForbidden();
});

it('accepts generate trigger with mcp:write ability', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    Sanctum::actingAs($this->admin, ['mcp:write']);
    $op = makeOperator('legendary');

    $this->postJson('/api/asset3d/generate', [
        'entity_type' => 'operator',
        'slug'        => $op->slug,
    ])
        ->assertStatus(202)
        ->assertJsonStructure(['entity_type', 'slug', 'meshy_task_id', 'status']);

    expect($op->fresh()->base_generation_status)->toBe('queued');
    Queue::assertPushed(PollMeshyTaskJob::class);
});

// ─── Endpoints ────────────────────────────────────────────────────────

it('returns operator details with skins and accessories loaded', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    $op = makeOperator('legendary');
    OperatorSkin::factory()->create(['operator_id' => $op->id, 'is_active' => true]);
    OperatorSkin::factory()->create(['operator_id' => $op->id, 'is_active' => false]);
    $a = Accessory::factory()->slot('head')->create();
    $op->accessories()->attach($a->id, ['is_default' => true]);

    $resp = $this->getJson("/api/asset3d/operators/{$op->slug}")->assertOk()->json();

    expect($resp['data']['skins'])->toHaveCount(1); // inactive filtré
    expect($resp['data']['accessories'])->toHaveCount(1);
});

it('looks up operator by codename as fallback', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    $op = makeOperator('legendary');

    $this->getJson("/api/asset3d/operators/{$op->codename}")
        ->assertOk()
        ->assertJsonPath('data.codename', $op->codename);
});

it('lists weapons filterable by category', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    Weapon::factory()->create(['category' => 'sniper', 'name' => 'Long Eye']);
    Weapon::factory()->create(['category' => 'pistol', 'name' => 'Hand Cannon']);

    $list = $this->getJson('/api/asset3d/weapons?category=sniper')->assertOk()->json('data');

    expect($list)->toHaveCount(1);
    expect($list[0]['category'])->toBe('sniper');
});

it('lists accessories filterable by slot', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    Accessory::factory()->slot('head')->create();
    Accessory::factory()->slot('back')->create();

    $list = $this->getJson('/api/asset3d/accessories?slot=head')->assertOk()->json('data');

    expect($list)->toHaveCount(1);
    expect($list[0]['slot'])->toBe('head');
});

it('exposes generation status by entity', function () {
    Sanctum::actingAs($this->admin, ['mcp:read']);
    $op = makeOperator('legendary');
    $op->update(['base_generation_status' => 'generating', 'base_meshy_task_id' => 'tsk-42']);

    $this->getJson("/api/asset3d/status/operator/{$op->slug}")
        ->assertOk()
        ->assertJson([
            'entity_type'       => 'operator',
            'slug'              => $op->slug,
            'generation_status' => 'generating',
            'meshy_task_id'     => 'tsk-42',
        ]);
});

it('validates the trigger payload', function () {
    Sanctum::actingAs($this->admin, ['mcp:write']);

    $this->postJson('/api/asset3d/generate', [
        'entity_type' => 'spaceship',  // invalide
        'slug'        => 'foo',
    ])->assertStatus(422)->assertJsonValidationErrors(['entity_type']);
});

it('returns 409 when retrying a ready operator without force', function () {
    Sanctum::actingAs($this->admin, ['mcp:write']);
    $op = makeOperator('legendary');
    $op->update(['base_generation_status' => 'ready', 'base_model_url' => 'x.glb']);

    $this->postJson('/api/asset3d/generate', [
        'entity_type' => 'operator',
        'slug'        => $op->slug,
    ])->assertStatus(409)->assertJsonPath('error', 'meshy_generation_refused');
});
