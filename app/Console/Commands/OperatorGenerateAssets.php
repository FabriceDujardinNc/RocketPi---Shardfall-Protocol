<?php

namespace App\Console\Commands;

use App\Models\Operator;
use App\Services\Meshy\MeshyException;
use App\Services\Meshy\MeshyGenerationService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Déclenche la génération 3D Meshy.ai pour un opérateur et ses variantes.
 *
 * Usage :
 *   php artisan operator:generate-assets vex --base
 *   php artisan operator:generate-assets vex --skins --weapons
 *   php artisan operator:generate-assets vex --all
 *   php artisan operator:generate-assets vex --all --force        (relance même si ready)
 *   php artisan operator:generate-assets vex --all --dry-run      (montre ce qui serait fait)
 *
 * Le job concret est dispatché en queue `meshy` — la commande retourne
 * immédiatement après création des tâches Meshy.
 */
class OperatorGenerateAssets extends Command
{
    protected $signature = 'operator:generate-assets
                            {operator : Slug ou codename de l\'opérateur}
                            {--base : Génère le mesh de base}
                            {--skins : Génère les textures skin}
                            {--weapons : Génère les armes liées (via player_loadouts)}
                            {--accessories : Génère les accessoires liés}
                            {--all : Équivaut à --base --skins --weapons --accessories}
                            {--force : Régénère même les entités déjà ready}
                            {--dry-run : Liste les entités sans lancer la génération}';

    protected $description = 'Lance la génération Meshy.ai pour un opérateur (mesh base, skins, armes, accessoires).';

    public function handle(MeshyGenerationService $service): int
    {
        $key = $this->argument('operator');

        $operator = Operator::query()
            ->where('slug', $key)
            ->orWhere('codename', $key)
            ->first();

        if (! $operator) {
            $this->error("Opérateur introuvable : {$key} (cherché slug + codename)");
            return self::FAILURE;
        }

        [$doBase, $doSkins, $doWeapons, $doAccessories] = $this->resolveFlags();

        $force  = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $targets = [];

        if ($doBase) {
            $targets[] = ['Operator', $operator, "{$operator->codename} — base mesh"];
        }
        if ($doSkins) {
            foreach ($operator->skins as $skin) {
                $targets[] = ['OperatorSkin', $skin, "skin {$skin->slug}"];
            }
        }
        if ($doAccessories) {
            foreach ($operator->accessories as $accessory) {
                $targets[] = ['Accessory', $accessory, "accessory {$accessory->slug} ({$accessory->slot})"];
            }
        }
        if ($doWeapons) {
            $this->warn('--weapons : génération via player_loadouts pas encore implémentée. '
                . 'Lance pour le moment avec `php artisan tinker` ou ajoute un mapping operator→weapons.');
        }

        if (empty($targets)) {
            $this->error('Aucune cible à générer. Précise au moins un flag (--base, --skins, --accessories, --all).');
            return self::FAILURE;
        }

        $this->info("Cibles à générer pour {$operator->codename} :");
        foreach ($targets as [$type, $entity, $label]) {
            $this->line("  - [{$type}] {$label}");
        }

        if ($dryRun) {
            $this->info('Dry-run : aucune génération lancée.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Lancer la génération Meshy pour {$operator->codename} ?", true)) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        $success = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($targets as [$type, $entity, $label]) {
            try {
                $taskId = $service->generate($entity, force: $force);
                $this->line("  ✓ {$label} → task {$taskId}");
                $success++;
            } catch (MeshyException $e) {
                $this->warn("  ↷ {$label} : {$e->getMessage()}");
                $skipped++;
            } catch (Throwable $e) {
                $this->error("  ✗ {$label} : {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("OK: {$success} · skipped: {$skipped} · failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Résout les flags --base/--skins/--weapons/--accessories en respectant --all.
     *
     * @return array{0:bool,1:bool,2:bool,3:bool}
     */
    private function resolveFlags(): array
    {
        if ($this->option('all')) {
            return [true, true, true, true];
        }

        return [
            (bool) $this->option('base'),
            (bool) $this->option('skins'),
            (bool) $this->option('weapons'),
            (bool) $this->option('accessories'),
        ];
    }
}
