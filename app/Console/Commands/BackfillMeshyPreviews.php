<?php

namespace App\Console\Commands;

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Re-télécharge les previews (thumbnail Meshy) pour toutes les entités 3D déjà
 * en statut `ready` mais qui n'ont pas de preview_url persistée.
 *
 * Utile quand on enrichit le pipeline a posteriori (ex. ajout de la colonne
 * base_preview_url sur operators). Aucun crédit Meshy consommé : on appelle
 * juste GET /status sur les task_id déjà payés.
 *
 *   php artisan meshy:backfill-previews          (toutes entités, dry run avant)
 *   php artisan meshy:backfill-previews --force  (sans confirmation)
 *   php artisan meshy:backfill-previews --kind=operator   (filtre)
 */
class BackfillMeshyPreviews extends Command
{
    protected $signature = 'meshy:backfill-previews
                            {--kind= : Filtre par type : operator|skin|accessory}
                            {--force : N\'attend pas de confirmation interactive}';

    protected $description = 'Re-télécharge les previews Meshy manquantes sur les entités déjà ready (gratuit)';

    public function handle(MeshyClientInterface $client): int
    {
        $kind = $this->option('kind');
        $targets = $this->collectTargets($kind);

        $count = count($targets);
        if ($count === 0) {
            $this->info('Aucune entité à backfill.');
            return self::SUCCESS;
        }

        $this->info("Entités à backfill ({$count}) :");
        foreach ($targets as [$entity, $entityKind]) {
            $this->line("  - {$entityKind} {$entity->slug} (task {$entity->{$this->taskIdCol($entity)}})");
        }

        if (! $this->option('force') && ! $this->confirm("Lancer les {$count} re-poll Meshy ? (gratuit, juste GET /status)", true)) {
            return self::FAILURE;
        }

        $ok = $failed = 0;
        foreach ($targets as [$entity, $entityKind]) {
            try {
                $this->backfillOne($entity, $entityKind, $client);
                $ok++;
                $this->info("  ✓ {$entityKind} {$entity->slug}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$entityKind} {$entity->slug} → {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. OK={$ok} failed={$failed}");
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<int, array{0: Model, 1: string}> */
    private function collectTargets(?string $kind): array
    {
        $targets = [];

        if (! $kind || $kind === 'operator') {
            foreach (Operator::query()
                ->where('base_generation_status', 'ready')
                ->whereNotNull('base_meshy_task_id')
                ->whereNull('base_preview_url')
                ->get() as $o) {
                $targets[] = [$o, 'operator'];
            }
        }
        if (! $kind || $kind === 'skin') {
            foreach (OperatorSkin::query()
                ->where('generation_status', 'ready')
                ->whereNotNull('meshy_task_id')
                ->whereNull('preview_url')
                ->get() as $s) {
                $targets[] = [$s, 'skin'];
            }
        }
        if (! $kind || $kind === 'accessory') {
            foreach (Accessory::query()
                ->where('generation_status', 'ready')
                ->whereNotNull('meshy_task_id')
                ->whereNull('preview_url')
                ->get() as $a) {
                $targets[] = [$a, 'accessory'];
            }
        }

        return $targets;
    }

    private function backfillOne(Model $entity, string $entityKind, MeshyClientInterface $client): void
    {
        // Le kind à passer au client n'est pas la même chose que la table :
        // 'skin' route vers /v1/retexture, les autres vers /v2/text-to-3d.
        $clientKind = $entityKind === 'skin' ? 'skin' : null;
        $taskId     = $entity->{$this->taskIdCol($entity)};

        $status = $client->status($taskId, $clientKind);

        if (! $status->previewUrl) {
            throw new \RuntimeException('No thumbnail_url returned by Meshy');
        }

        $disk = Storage::disk('public');
        $slug = $entity->slug;

        if ($entity instanceof Operator) {
            $path = "models/operators/{$slug}/preview.png";
            $disk->put($path, $client->downloadAsset($status->previewUrl));
            $entity->base_preview_url = $path;
        } elseif ($entity instanceof OperatorSkin) {
            $opSlug = $entity->operator->slug;
            $path   = "models/operators/{$opSlug}/skins/{$slug}/preview.png";
            $disk->put($path, $client->downloadAsset($status->previewUrl));
            $entity->preview_url = $path;
        } elseif ($entity instanceof Accessory) {
            $path = "models/accessories/{$slug}/preview.png";
            $disk->put($path, $client->downloadAsset($status->previewUrl));
            $entity->preview_url = $path;
        }
        $entity->save();
    }

    private function taskIdCol(Model $entity): string
    {
        return $entity instanceof Operator ? 'base_meshy_task_id' : 'meshy_task_id';
    }
}
