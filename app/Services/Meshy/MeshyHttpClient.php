<?php

namespace App\Services\Meshy;

use App\Services\Meshy\Contracts\MeshyClientInterface;
use App\Services\Meshy\Data\MeshyGenerationRequest;
use App\Services\Meshy\Data\MeshyTaskStatus;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Implémentation HTTP réelle de Meshy.ai.
 *
 * Endpoints utilisés (Meshy v2) :
 *   - POST /v2/text-to-3d          (kind=base|weapon|accessory)
 *   - POST /v2/text-to-3d/{id}/refine  (kind=skin, retexture sur mesh existant)
 *   - GET  /v2/text-to-3d/{id}     (status)
 *
 * Mapping statuts Meshy → internes :
 *   PENDING       → queued
 *   IN_PROGRESS   → generating
 *   SUCCEEDED     → ready
 *   FAILED/EXPIRED→ failed
 *
 * Les retries sont gérés par PendingRequest::retry(3, 200, throw: false).
 */
class MeshyHttpClient implements MeshyClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {
    }

    public function create(MeshyGenerationRequest $request): string
    {
        $payload = match ($request->kind) {
            'base', 'weapon', 'accessory' => [
                'mode'              => 'preview',
                'prompt'            => $request->prompt,
                'negative_prompt'   => $request->negativePrompt,
                'art_style'         => $request->artStyle,
                'topology'          => 'triangle',
                'target_polycount'  => $request->polycountTarget,
            ],
            'skin' => [
                // text-to-texture sur mesh existant : Meshy lit base_model_url
                'mode'              => 'retexture',
                'prompt'            => $request->prompt,
                'negative_prompt'   => $request->negativePrompt,
                'art_style'         => $request->artStyle,
                'model_url'         => $request->baseModelUrl,
            ],
            default => throw new MeshyException("Unknown kind: {$request->kind}"),
        };

        try {
            $response = $this->client()
                ->post('/v2/text-to-3d', array_filter($payload, fn ($v) => $v !== null))
                ->throw();

            $data = $response->json();
            $taskId = $data['result'] ?? $data['task_id'] ?? null;

            if (! $taskId) {
                throw new MeshyException('Meshy returned no task id: ' . json_encode($data));
            }

            return (string) $taskId;
        } catch (RequestException $e) {
            throw new MeshyException(
                'Meshy create failed: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    public function status(string $taskId): MeshyTaskStatus
    {
        try {
            $response = $this->client()
                ->get("/v2/text-to-3d/{$taskId}")
                ->throw();

            $data = $response->json();

            $internalStatus = match (strtoupper($data['status'] ?? '')) {
                'PENDING'                                 => 'queued',
                'IN_PROGRESS', 'PROCESSING'               => 'generating',
                'SUCCEEDED', 'SUCCESS', 'COMPLETED'       => 'ready',
                'FAILED', 'EXPIRED', 'CANCELED'           => 'failed',
                default                                   => 'generating',
            };

            return new MeshyTaskStatus(
                taskId: $taskId,
                status: $internalStatus,
                modelUrl: $data['model_urls']['glb'] ?? null,
                textureUrl: $data['texture_urls'][0] ?? null,
                previewUrl: $data['thumbnail_url'] ?? ($data['preview_url'] ?? null),
                errorMessage: $data['task_error']['message'] ?? null,
                progress: isset($data['progress']) ? (int) $data['progress'] : null,
            );
        } catch (RequestException $e) {
            throw new MeshyException(
                "Meshy status failed for {$taskId}: " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    public function downloadAsset(string $url): string
    {
        try {
            $response = $this->http
                ->retry(2, 500, throw: false)
                ->timeout(60)
                ->get($url)
                ->throw();

            return $response->body();
        } catch (Throwable $e) {
            throw new MeshyException(
                "Meshy download failed ({$url}): " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(3, 200, throw: false);
    }
}
