<?php

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\User;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->op = makeOperator('legendary');
    $this->op->update([
        'base_generation_status' => 'ready',
        'base_meshy_task_id'     => 'fake-preview-123',
        'base_model_url'         => 'models/operators/test/base.glb',
    ]);
});

it('refine returns a new task_id and switches the operator to queued', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $service = app(MeshyGenerationService::class);

    $newTaskId = $service->refine($this->op);

    expect($newTaskId)->not->toBe('fake-preview-123');
    expect($this->op->fresh()->base_generation_status)->toBe('queued');
    expect($this->op->fresh()->base_meshy_task_id)->toBe($newTaskId);
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('refuses to refine when no preview task_id is set', function () {
    $this->op->update(['base_meshy_task_id' => null]);
    $service = app(MeshyGenerationService::class);

    $service->refine($this->op);
})->throws(MeshyException::class, 'No preview task_id');

it('refuses to refine when status is not ready', function () {
    $this->op->update(['base_generation_status' => 'pending']);
    $service = app(MeshyGenerationService::class);

    $service->refine($this->op);
})->throws(MeshyException::class, 'must be ready first');

it('refuses to refine an OperatorSkin (skins use retexture, not refine)', function () {
    $skin = OperatorSkin::factory()->ready()->create(['operator_id' => $this->op->id]);
    $service = app(MeshyGenerationService::class);

    $service->refine($skin);
})->throws(MeshyException::class, 'not applicable to OperatorSkin');

it('refine works on Accessory ready with task_id', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $accessory = Accessory::factory()->slot('head')->ready()->create([
        'meshy_task_id' => 'fake-acc-task',
    ]);
    $service = app(MeshyGenerationService::class);

    $newTaskId = $service->refine($accessory);

    expect($newTaskId)->not->toBe('fake-acc-task');
    expect($accessory->fresh()->generation_status)->toBe('queued');
    Queue::assertPushed(PollMeshyTaskJob::class);
});

it('admin can trigger refine via http route', function () {
    Queue::fake([PollMeshyTaskJob::class]);
    $admin = makeUser(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post("/admin/operators/{$this->op->slug}/assets/refine")
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($this->op->fresh()->base_generation_status)->toBe('queued');
});

it('non-admin gets 403 on refine route', function () {
    $user = makeUser(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->post("/admin/operators/{$this->op->slug}/assets/refine")
        ->assertForbidden();
});
