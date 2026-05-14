<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use App\Services\Meshy\FakeMeshyClient;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // On s'assure d'un fake client (le binding par défaut est déjà fake en
    // tests via MESHY_FAKE/no api key, mais on force pour être explicite).
    $this->app->singleton(MeshyClientInterface::class, fn () => new FakeMeshyClient());
    $this->client = app(MeshyClientInterface::class);
    $this->service = app(MeshyGenerationService::class);

    $this->op = makeOperator('legendary');
});

it('generates a base mesh for an operator and dispatches the polling job', function () {
    Queue::fake();

    $taskId = $this->service->generate($this->op);

    expect($taskId)->toStartWith('fake-');
    expect($this->op->fresh()->base_generation_status)->toBe('queued');
    expect($this->op->fresh()->base_meshy_task_id)->toBe($taskId);

    Queue::assertPushedOn('meshy', PollMeshyTaskJob::class);
});

it('refuses to regenerate a ready operator without --force', function () {
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_meshy_task_id'     => 'old-task',
    ]);

    expect(fn () => $this->service->generate($this->op))
        ->toThrow(MeshyException::class, 'already in status');
});

it('regenerates a failed operator without force flag', function () {
    Queue::fake();
    $this->op->update([
        'base_generation_status' => 'failed',
        'base_meshy_task_id'     => 'old-task',
    ]);

    $taskId = $this->service->generate($this->op);

    expect($taskId)->not->toBe('old-task');
    expect($this->op->fresh()->base_generation_status)->toBe('queued');
});

it('regenerates a ready operator when force=true', function () {
    Queue::fake();
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_meshy_task_id'     => 'old-task',
        'base_model_url'         => 'models/operators/old/base.glb',
    ]);

    $taskId = $this->service->generate($this->op, force: true);

    expect($taskId)->toStartWith('fake-');
    expect($this->op->fresh()->base_generation_status)->toBe('queued');
});

it('generates a weapon and tags status queued', function () {
    Queue::fake();
    $weapon = Weapon::factory()->create(['name' => 'Pulse Rifle']);

    $taskId = $this->service->generate($weapon);

    expect($weapon->fresh()->generation_status)->toBe('queued');
    expect($weapon->fresh()->meshy_task_id)->toBe($taskId);
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('generates an accessory and tags status queued', function () {
    Queue::fake();
    $a = Accessory::factory()->slot('head')->create(['name' => 'Combat Helmet']);

    $taskId = $this->service->generate($a);

    expect($a->fresh()->generation_status)->toBe('queued');
    expect($a->fresh()->meshy_task_id)->toBe($taskId);
});

it('generates an operator skin with text-to-texture', function () {
    Queue::fake();
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_model_url'         => 'models/operators/' . $this->op->id . '/base.glb',
    ]);

    $skin = OperatorSkin::factory()->create([
        'operator_id' => $this->op->id,
        'name'        => 'Neon Variant',
    ]);

    $taskId = $this->service->generate($skin);

    expect($skin->fresh()->generation_status)->toBe('queued');
    expect($skin->fresh()->meshy_task_id)->toBe($taskId);
});
