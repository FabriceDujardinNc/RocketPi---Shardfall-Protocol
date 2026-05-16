<?php

namespace App\Jobs;

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Models\WeaponSkin;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Job de polling pour une tâche Meshy en cours.
 *
 *  - Charge l'entité (Operator|OperatorSkin|Weapon|Accessory) avec
 *    lockForUpdate pour éviter races entre 2 polls concurrents
 *  - Lit le statut Meshy (queued/generating/ready/failed)
 *  - Si ready : télécharge le .glb (ou .png pour skin) et le persiste sur
 *    Storage::disk('public')/models/{kind}/{slug}/...
 *  - Si failed : marque la row failed + log l'erreur
 *  - Sinon : re-dispatch dans poll_interval_seconds (queue meshy)
 *
 * Le job ne fait JAMAIS plus d'un télécharge / poll par invocation pour
 * rester rapide et permettre à Horizon de paralléliser.
 */
class PollMeshyTaskJob implements ShouldQueue
{
    use FoundationQueueable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 30;

    public function __construct(
        public string $entityClass,
        public int|string $entityKey,
    ) {
    }

    public function handle(
        MeshyClientInterface $client,
        MeshyGenerationService $service,
    ): void {
        /** @var class-string<Model> $cls */
        $cls = $this->entityClass;

        DB::transaction(function () use ($cls, $client, $service) {
            /** @var Operator|OperatorSkin|Weapon|WeaponSkin|Accessory|null $entity */
            $entity = $cls::query()->whereKey($this->entityKey)->lockForUpdate()->first();

            if (! $entity) {
                Log::warning("PollMeshyTaskJob: entity {$cls}#{$this->entityKey} not found, dropping");
                return;
            }

            [$statusCol, $taskIdCol] = $service->columnsFor($entity);
            $taskId = $entity->{$taskIdCol};

            if (! $taskId) {
                Log::warning("PollMeshyTaskJob: no task id on {$cls}#{$this->entityKey}, dropping");
                return;
            }

            // Le kind sert au client HTTP à router vers le bon endpoint Meshy
            // (text-to-3d vs retexture pour les skins).
            $kind = $entity instanceof OperatorSkin ? 'skin' : null;
            $status = $client->status($taskId, $kind);

            if ($status->isFailed()) {
                $entity->{$statusCol} = 'failed';
                $entity->save();
                Log::error("Meshy task {$taskId} failed: {$status->errorMessage}");
                return;
            }

            if (! $status->isReady()) {
                // queued / generating → on met à jour le statut interne et on
                // re-dispatch pour un nouveau poll.
                $entity->{$statusCol} = 'generating';
                $entity->save();

                $delay = (int) config('services.meshy.poll_interval_seconds', 15);
                self::dispatch($cls, $this->entityKey)
                    ->onQueue('meshy')
                    ->delay(now()->addSeconds($delay));

                return;
            }

            // status === ready → on télécharge et on persiste
            $this->persistAssets($entity, $status, $client);
            $entity->{$statusCol} = 'ready';
            $entity->save();
        });
    }

    /**
     * Télécharge model/texture/preview et stocke sur Storage::disk('public').
     * Met à jour les colonnes URL de l'entité.
     */
    private function persistAssets(Model $entity, $status, MeshyClientInterface $client): void
    {
        $disk = Storage::disk('public');
        $slug = $entity->slug ?? (string) $entity->getKey();

        if ($entity instanceof Operator) {
            $path = "models/operators/{$slug}/base.glb";
            $disk->put($path, $client->downloadAsset($status->modelUrl));
            $entity->base_model_url = $path;
            if ($status->previewUrl) {
                $previewPath = "models/operators/{$slug}/preview.png";
                $disk->put($previewPath, $client->downloadAsset($status->previewUrl));
                $entity->base_preview_url = $previewPath;
            }
        } elseif ($entity instanceof OperatorSkin) {
            // Meshy v1/retexture renvoie un .glb retexturé (model_urls.glb) en plus
            // de la texture brute. On télécharge les deux : le .glb est self-contained
            // (texture bakée, UVs cohérentes) et c'est ce qu'on rend dans le viewer
            // admin ainsi que ce que Unity peut charger directement. La texture.png
            // reste utile pour debug et pour une future application via MaterialPropertyBlock.
            $baseDir = "models/operators/{$entity->operator->slug}/skins/{$slug}";
            if ($status->modelUrl) {
                $modelPath = "{$baseDir}/model.glb";
                $disk->put($modelPath, $client->downloadAsset($status->modelUrl));
                $entity->model_url = $modelPath;
            }
            if ($status->textureUrl) {
                $texturePath = "{$baseDir}/texture.png";
                $disk->put($texturePath, $client->downloadAsset($status->textureUrl));
                $entity->texture_url = $texturePath;
            }
            if ($status->previewUrl) {
                $previewPath = "{$baseDir}/preview.png";
                $disk->put($previewPath, $client->downloadAsset($status->previewUrl));
                $entity->preview_url = $previewPath;
            }
        } elseif ($entity instanceof Weapon) {
            $path = "models/weapons/{$slug}/base.glb";
            $disk->put($path, $client->downloadAsset($status->modelUrl));
            $entity->base_model_url = $path;
        } elseif ($entity instanceof Accessory) {
            $path = "models/accessories/{$slug}/base.glb";
            $disk->put($path, $client->downloadAsset($status->modelUrl));
            $entity->base_model_url = $path;
            if ($status->previewUrl) {
                $previewPath = "models/accessories/{$slug}/preview.png";
                $disk->put($previewPath, $client->downloadAsset($status->previewUrl));
                $entity->preview_url = $previewPath;
            }
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error(sprintf(
            'PollMeshyTaskJob exhausted retries for %s#%s: %s',
            $this->entityClass,
            $this->entityKey,
            $e->getMessage(),
        ));

        // Marquer la row failed en best-effort (hors transaction).
        try {
            /** @var class-string<Model> $cls */
            $cls    = $this->entityClass;
            $entity = $cls::query()->whereKey($this->entityKey)->first();
            if ($entity) {
                $service = app(MeshyGenerationService::class);
                [$statusCol] = $service->columnsFor($entity);
                $entity->{$statusCol} = 'failed';
                $entity->save();
            }
        } catch (Throwable) {
            // swallow
        }
    }
}
