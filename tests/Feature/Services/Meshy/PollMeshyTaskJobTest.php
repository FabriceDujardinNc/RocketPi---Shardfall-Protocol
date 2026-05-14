<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use App\Services\Meshy\FakeMeshyClient;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->app->singleton(MeshyClientInterface::class, fn () => new FakeMeshyClient());
    $this->client = app(MeshyClientInterface::class);
    $this->service = app(MeshyGenerationService::class);
});

it('persists a downloaded base.glb on storage when task ready', function () {
    $op = makeOperator('legendary');
    $taskId = $this->service->generate($op);

    // Poll synchrone
    (new PollMeshyTaskJob(Operator::class, $op->id))->handle($this->client, $this->service);

    $op->refresh();
    expect($op->base_generation_status)->toBe('ready');
    expect($op->base_model_url)->toBe("models/operators/{$op->slug}/base.glb");
    Storage::disk('public')->assertExists($op->base_model_url);
});

it('marks operator failed when meshy returns failed status', function () {
    Bus::fake();
    $op = makeOperator('legendary');
    $this->service->generate($op);
    /** @var FakeMeshyClient $fake */
    $fake = $this->client;
    $fake->forceFailure(true);

    (new PollMeshyTaskJob(Operator::class, $op->id))->handle($this->client, $this->service);

    expect($op->fresh()->base_generation_status)->toBe('failed');
});

it('keeps generating status and re-dispatches when task still in progress', function () {
    Bus::fake();
    $op = makeOperator('legendary');
    $this->service->generate($op);
    /** @var FakeMeshyClient $fake */
    $fake = $this->client;
    $fake->forcePending(true);

    (new PollMeshyTaskJob(Operator::class, $op->id))->handle($this->client, $this->service);

    expect($op->fresh()->base_generation_status)->toBe('generating');
    Bus::assertDispatched(PollMeshyTaskJob::class);
});

it('persists weapon glb on storage', function () {
    $weapon = Weapon::factory()->create(['name' => 'Pulse Rifle']);
    $this->service->generate($weapon);

    (new PollMeshyTaskJob(Weapon::class, $weapon->id))->handle($this->client, $this->service);

    $weapon->refresh();
    expect($weapon->generation_status)->toBe('ready');
    expect($weapon->base_model_url)->toBe("models/weapons/{$weapon->slug}/base.glb");
    Storage::disk('public')->assertExists($weapon->base_model_url);
});

it('persists accessory glb on storage', function () {
    $a = Accessory::factory()->slot('head')->create(['name' => 'Combat Helmet']);
    $this->service->generate($a);

    (new PollMeshyTaskJob(Accessory::class, $a->id))->handle($this->client, $this->service);

    $a->refresh();
    expect($a->generation_status)->toBe('ready');
    expect($a->base_model_url)->toBe("models/accessories/{$a->slug}/base.glb");
    Storage::disk('public')->assertExists($a->base_model_url);
});

it('persists skin texture under operator skin path', function () {
    $op = makeOperator('legendary');
    $op->update([
        'base_generation_status' => 'ready',
        'base_model_url'         => "models/operators/{$op->slug}/base.glb",
    ]);

    $skin = OperatorSkin::factory()->create([
        'operator_id' => $op->id,
        'name'        => 'Neon Variant',
    ]);

    $this->service->generate($skin);
    (new PollMeshyTaskJob(OperatorSkin::class, $skin->id))->handle($this->client, $this->service);

    $skin->refresh();
    expect($skin->generation_status)->toBe('ready');
    expect($skin->texture_url)->toBe("models/operators/{$op->slug}/skins/{$skin->slug}/texture.png");
    Storage::disk('public')->assertExists($skin->texture_url);
});
