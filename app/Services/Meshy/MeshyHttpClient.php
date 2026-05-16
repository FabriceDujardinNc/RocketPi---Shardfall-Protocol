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
 * Endpoints utilisés :
 *   - POST /openapi/v2/text-to-3d          (kind=base|weapon|accessory, mode=preview)
 *   - GET  /openapi/v2/text-to-3d/{id}     (status pour kind base|weapon|accessory)
 *   - POST /openapi/v1/retexture           (kind=skin, retexture sur mesh existant)
 *   - GET  /openapi/v1/retexture/{id}      (status pour kind=skin)
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
        [$endpoint, $payload] = match ($request->kind) {
            'base', 'weapon', 'accessory' => ['/openapi/v2/text-to-3d', [
                'mode'              => 'preview',
                'prompt'            => $request->prompt,
                'negative_prompt'   => $request->negativePrompt,
                'art_style'         => $request->artStyle,
                'topology'          => 'triangle',
                'target_polycount'  => $request->polycountTarget,
            ]],
            // Retexture = endpoint v1 dédié. Payload Meshy v1/retexture :
            //  - model_url            : URL absolue publique du .glb base à retexturer
            //  - text_style_prompt    : description du style texture (max 600 chars)
            //  - pas de mode / negative_prompt / art_style — non supportés ici
            'skin' => ['/openapi/v1/retexture', [
                'model_url'         => $request->baseModelUrl,
                'text_style_prompt' => mb_substr($request->prompt, 0, 600),
                'enable_pbr'        => false,
            ]],
            default => throw new MeshyException("Unknown kind: {$request->kind}"),
        };

        if (! empty($payload['model_url']) && ! preg_match('#^https?://#', (string) $payload['model_url'])) {
            throw new MeshyException(
                "Meshy retexture requires an absolute http(s) model_url, got: {$payload['model_url']}"
            );
        }

        try {
            $response = $this->client()
                ->post($endpoint, array_filter($payload, fn ($v) => $v !== null))
                ->throw();

            $data = $response->json();
            $taskId = $data['result'] ?? $data['task_id'] ?? $data['id'] ?? null;

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

    public function refine(string $previewTaskId): string
    {
        try {
            $response = $this->client()
                ->post('/openapi/v2/text-to-3d', [
                    'mode'             => 'refine',
                    'preview_task_id'  => $previewTaskId,
                    'enable_pbr'       => true,
                ])
                ->throw();

            $data = $response->json();
            $taskId = $data['result'] ?? $data['task_id'] ?? $data['id'] ?? null;

            if (! $taskId) {
                throw new MeshyException('Meshy refine returned no task id: ' . json_encode($data));
            }
            return (string) $taskId;
        } catch (RequestException $e) {
            throw new MeshyException(
                "Meshy refine failed (preview {$previewTaskId}): " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    public function status(string $taskId, ?string $kind = null): MeshyTaskStatus
    {
        $endpoint = $kind === 'skin'
            ? "/openapi/v1/retexture/{$taskId}"
            : "/openapi/v2/text-to-3d/{$taskId}";

        try {
            $response = $this->client()
                ->get($endpoint)
                ->throw();

            $data = $response->json();

            $internalStatus = match (strtoupper($data['status'] ?? '')) {
                'PENDING'                                 => 'queued',
                'IN_PROGRESS', 'PROCESSING'               => 'generating',
                'SUCCEEDED', 'SUCCESS', 'COMPLETED'       => 'ready',
                'FAILED', 'EXPIRED', 'CANCELED'           => 'failed',
                default                                   => 'generating',
            };

            // texture_urls : tableau d'objets sur text-to-3d, objet unique sur retexture.
            // On normalise vers une URL (base_color en priorité côté retexture).
            $textureUrl = null;
            if (isset($data['texture_urls'])) {
                $tex = $data['texture_urls'];
                if (is_array($tex) && array_is_list($tex) && ! empty($tex)) {
                    $textureUrl = is_array($tex[0]) ? ($tex[0]['base_color'] ?? null) : $tex[0];
                } elseif (is_array($tex)) {
                    $textureUrl = $tex['base_color'] ?? null;
                }
            }

            return new MeshyTaskStatus(
                taskId: $taskId,
                status: $internalStatus,
                modelUrl: $data['model_urls']['glb'] ?? null,
                textureUrl: $textureUrl,
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
