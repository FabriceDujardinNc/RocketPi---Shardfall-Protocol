<?php

namespace App\Services\Meshy;

use App\Jobs\PollMeshyTaskJob;
use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Services\Meshy\Contracts\MeshyClientInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrateur de génération 3D :
 *  1. Reçoit une entité Eloquent (Operator | OperatorSkin | Weapon | Accessory)
 *  2. Verrouille la row (lockForUpdate), refuse si déjà en cours ou ready
 *  3. Construit le prompt via MeshyPromptBuilder
 *  4. Crée la tâche Meshy via MeshyClient
 *  5. Persiste task_id + status=queued
 *  6. Dispatch PollMeshyTaskJob pour le suivi
 *
 * Le service NE télécharge PAS lui-même les assets — c'est le job (queue
 * dédiée `meshy`) qui poll, télécharge et stocke. On garde le service
 * synchrone pour la création.
 *
 * Idempotence : un appel sur une entité déjà `ready` est un no-op (sauf si
 * `force=true`, qui repasse à pending). Un appel sur une entité `failed`
 * relance proprement (status repasse à queued).
 */
class MeshyGenerationService
{
    public function __construct(
        private readonly MeshyClientInterface $client,
        private readonly MeshyPromptBuilder $promptBuilder,
    ) {
    }

    /**
     * Lance la génération d'une entité. Retourne le task_id Meshy.
     */
    public function generate(Model $entity, bool $force = false): string
    {
        return DB::transaction(function () use ($entity, $force) {
            /** @var Operator|OperatorSkin|Weapon|Accessory $fresh */
            $fresh = $entity->newQuery()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();

            [$statusCol, $taskIdCol] = $this->columnsFor($fresh);

            $currentStatus = $fresh->{$statusCol};

            if (! $force && in_array($currentStatus, ['ready', 'queued', 'generating'], true)) {
                throw new MeshyException(
                    sprintf(
                        'Entity %s#%d is already in status "%s". Use force=true to retry.',
                        get_class($fresh),
                        $fresh->getKey(),
                        $currentStatus,
                    )
                );
            }

            $request = $this->buildRequest($fresh);
            $taskId  = $this->client->create($request);

            $fresh->{$statusCol} = 'queued';
            $fresh->{$taskIdCol} = $taskId;
            $fresh->save();

            PollMeshyTaskJob::dispatch(get_class($fresh), $fresh->getKey())
                ->onQueue('meshy');

            return $taskId;
        });
    }

    /**
     * Lance un refine Meshy sur le mesh preview existant d'un opérateur/arme/accessoire.
     * Le refine applique les textures PBR qui manquent au preview (mode 'preview' de
     * /text-to-3d renvoie un mesh nu). Coûte ~10 crédits Meshy par task.
     *
     * Pré-requis : l'entité doit être en statut `ready` avec un task_id preview existant.
     * Le polling job remplacera le base.glb actuel par le glb texturé une fois ready.
     *
     * Skin n'est pas concerné — pour skin, utilise generate() qui passe par /retexture.
     */
    public function refine(Model $entity): string
    {
        return DB::transaction(function () use ($entity) {
            /** @var Operator|Weapon|Accessory $fresh */
            $fresh = $entity->newQuery()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh instanceof OperatorSkin) {
                throw new MeshyException('Refine is not applicable to OperatorSkin (skins use retexture).');
            }

            [$statusCol, $taskIdCol] = $this->columnsFor($fresh);
            $previewTaskId = $fresh->{$taskIdCol};

            if (! $previewTaskId) {
                throw new MeshyException(
                    sprintf('No preview task_id on %s#%d — cannot refine.', get_class($fresh), $fresh->getKey())
                );
            }

            if ($fresh->{$statusCol} !== 'ready') {
                throw new MeshyException(
                    sprintf(
                        'Cannot refine %s#%d in status "%s". Preview must be ready first.',
                        get_class($fresh),
                        $fresh->getKey(),
                        $fresh->{$statusCol},
                    )
                );
            }

            $refineTaskId = $this->client->refine($previewTaskId);

            $fresh->{$statusCol} = 'queued';
            $fresh->{$taskIdCol} = $refineTaskId;
            $fresh->save();

            PollMeshyTaskJob::dispatch(get_class($fresh), $fresh->getKey())
                ->onQueue('meshy');

            return $refineTaskId;
        });
    }

    /**
     * Renvoie [status_column, meshy_task_id_column] pour l'entité.
     */
    public function columnsFor(Model $entity): array
    {
        return match (true) {
            $entity instanceof Operator     => ['base_generation_status', 'base_meshy_task_id'],
            $entity instanceof OperatorSkin => ['generation_status', 'meshy_task_id'],
            $entity instanceof Weapon       => ['generation_status', 'meshy_task_id'],
            $entity instanceof Accessory    => ['generation_status', 'meshy_task_id'],
            default => throw new InvalidArgumentException(
                'Unsupported entity for Meshy generation: ' . get_class($entity)
            ),
        };
    }

    private function buildRequest(Model $entity)
    {
        return match (true) {
            $entity instanceof Operator     => $this->promptBuilder->forOperatorBase($entity),
            $entity instanceof OperatorSkin => $this->promptBuilder->forOperatorSkin($entity),
            $entity instanceof Weapon       => $this->promptBuilder->forWeapon($entity),
            $entity instanceof Accessory    => $this->promptBuilder->forAccessory($entity),
            default => throw new InvalidArgumentException(
                'No prompt builder for: ' . get_class($entity)
            ),
        };
    }
}
