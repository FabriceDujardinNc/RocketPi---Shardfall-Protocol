<?php

namespace App\Services\Meshy;

use App\Services\Meshy\Contracts\MeshyClientInterface;
use App\Services\Meshy\Data\MeshyGenerationRequest;
use App\Services\Meshy\Data\MeshyTaskStatus;
use Illuminate\Support\Str;

/**
 * Implémentation fake pour tests + dev sans clé Meshy.
 *
 * Comportement par défaut : `create()` renvoie un task_id aléatoire,
 * `status()` répond toujours "ready" avec une URL placeholder pointant vers
 * un .glb statique (déposé manuellement dans le projet, cf. Phase 0).
 *
 * Pour tester les flows en erreur, exposer une méthode `forceFailure()` qui
 * fait basculer le prochain `status()` en "failed".
 */
class FakeMeshyClient implements MeshyClientInterface
{
    /** @var array<string, string> task_id → kind */
    private array $tasks = [];

    private bool $forcedFailure = false;
    private bool $forcedPending = false;

    public function create(MeshyGenerationRequest $request): string
    {
        $taskId = 'fake-' . Str::random(24);
        $this->tasks[$taskId] = $request->kind;

        return $taskId;
    }

    public function refine(string $previewTaskId): string
    {
        // Le fake renvoie un nouveau task_id qui sera ready immédiatement,
        // avec un model_url placeholder marqué "refined" pour distinguer.
        $taskId = 'fake-refine-' . Str::random(20);
        // Marqué comme kind base (ce que renvoie un refine côté Meshy = texturé)
        $this->tasks[$taskId] = 'base';
        return $taskId;
    }

    public function status(string $taskId, ?string $kind = null): MeshyTaskStatus
    {
        if (! isset($this->tasks[$taskId])) {
            throw new MeshyException("Unknown fake task id: {$taskId}");
        }

        if ($this->forcedFailure) {
            return new MeshyTaskStatus(
                taskId: $taskId,
                status: 'failed',
                modelUrl: null,
                textureUrl: null,
                previewUrl: null,
                errorMessage: 'fake forced failure',
                progress: 0,
            );
        }

        if ($this->forcedPending) {
            return new MeshyTaskStatus(
                taskId: $taskId,
                status: 'generating',
                modelUrl: null,
                textureUrl: null,
                previewUrl: null,
                errorMessage: null,
                progress: 42,
            );
        }

        $kind = $this->tasks[$taskId];

        return new MeshyTaskStatus(
            taskId: $taskId,
            status: 'ready',
            modelUrl: $kind === 'skin'
                ? null
                : "https://fake.meshy.local/{$taskId}/model.glb",
            textureUrl: in_array($kind, ['skin'], true)
                ? "https://fake.meshy.local/{$taskId}/texture.png"
                : null,
            previewUrl: "https://fake.meshy.local/{$taskId}/preview.png",
            errorMessage: null,
            progress: 100,
        );
    }

    public function downloadAsset(string $url): string
    {
        // Renvoie un GLB binaire factice — suffisant pour stocker, pas pour
        // être chargé par Unity. Les tests vérifient le STOCKAGE, pas l'asset.
        return "FAKE-GLB-CONTENT::{$url}";
    }

    public function forceFailure(bool $on = true): void
    {
        $this->forcedFailure = $on;
        if ($on) {
            $this->forcedPending = false;
        }
    }

    public function forcePending(bool $on = true): void
    {
        $this->forcedPending = $on;
        if ($on) {
            $this->forcedFailure = false;
        }
    }
}
