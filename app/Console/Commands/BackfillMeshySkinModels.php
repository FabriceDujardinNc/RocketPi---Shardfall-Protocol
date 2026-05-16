<?php

namespace App\Console\Commands;

use App\Models\OperatorSkin;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Re-télécharge le .glb retexturé pour les skins déjà en `ready` mais sans
 * `model_url` persisté (initialement on stockait juste la texture.png, mais
 * Meshy v1/retexture renvoie aussi un .glb self-contained qu'on néglige).
 *
 * 0 crédit consommé : on appelle GET /openapi/v1/retexture/{id} sur les tasks
 * déjà payées et on télécharge `model_urls.glb`.
 *
 *   php artisan meshy:backfill-skin-models
 *   php artisan meshy:backfill-skin-models --force
 *   php artisan meshy:backfill-skin-models --skin=vex-eclipse  (single)
 */
class BackfillMeshySkinModels extends Command
{
    protected $signature = 'meshy:backfill-skin-models
                            {--skin= : Slug d\'une skin spécifique (sinon toutes celles sans model_url)}
                            {--force : N\'attend pas de confirmation interactive}';

    protected $description = 'Re-télécharge les .glb retexturés Meshy manquants sur les skins ready (gratuit)';

    public function handle(MeshyClientInterface $client): int
    {
        $query = OperatorSkin::query()
            ->where('generation_status', 'ready')
            ->whereNotNull('meshy_task_id')
            ->whereNull('model_url');

        if ($skinSlug = $this->option('skin')) {
            $query->where('slug', $skinSlug);
        }

        $skins = $query->with('operator:id,slug')->get();

        if ($skins->isEmpty()) {
            $this->info('Aucune skin à backfill.');
            return self::SUCCESS;
        }

        $this->info("Skins à backfill ({$skins->count()}) :");
        foreach ($skins as $skin) {
            $this->line("  - {$skin->slug} (op={$skin->operator->slug}, task={$skin->meshy_task_id})");
        }

        if (! $this->option('force') && ! $this->confirm("Lancer les {$skins->count()} re-poll Meshy ? (gratuit, juste GET /status)", true)) {
            return self::FAILURE;
        }

        $ok = $failed = 0;
        foreach ($skins as $skin) {
            try {
                $this->backfillOne($skin, $client);
                $ok++;
                $this->info("  ✓ {$skin->slug}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$skin->slug} → {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. OK={$ok} failed={$failed}");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function backfillOne(OperatorSkin $skin, MeshyClientInterface $client): void
    {
        $status = $client->status($skin->meshy_task_id, 'skin');

        if (! $status->modelUrl) {
            throw new \RuntimeException('No model_urls.glb returned by Meshy (task may have expired)');
        }

        $disk    = Storage::disk('public');
        $opSlug  = $skin->operator->slug;
        $path    = "models/operators/{$opSlug}/skins/{$skin->slug}/model.glb";

        $disk->put($path, $client->downloadAsset($status->modelUrl));
        $skin->model_url = $path;
        $skin->save();
    }
}
