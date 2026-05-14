<?php

namespace App\Services\Meshy\Data;

/**
 * État retourné par Meshy lors d'un GET /tasks/{id}.
 *
 * Mappe les statuts de l'API Meshy (PENDING / IN_PROGRESS / SUCCEEDED /
 * FAILED) vers nos statuts internes (queued / generating / ready / failed).
 *
 * `model_url` est le .glb généré (présent uniquement quand status=ready).
 * `texture_url` est utilisé par les générations skin (texture-to-3d retexture).
 */
final readonly class MeshyTaskStatus
{
    public function __construct(
        public string $taskId,
        public string $status,         // queued|generating|ready|failed
        public ?string $modelUrl,
        public ?string $textureUrl,
        public ?string $previewUrl,
        public ?string $errorMessage,
        public ?int $progress,         // 0-100, si fourni par l'API
    ) {
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isTerminal(): bool
    {
        return $this->isReady() || $this->isFailed();
    }
}
