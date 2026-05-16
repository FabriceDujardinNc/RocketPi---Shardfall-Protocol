<?php

namespace App\Services\Meshy\Contracts;

use App\Services\Meshy\Data\MeshyGenerationRequest;
use App\Services\Meshy\Data\MeshyTaskStatus;

/**
 * Contrat unique pour tout adapter Meshy.ai (réel HTTP ou fake en dev/test).
 *
 * Le binding est résolu par le container Laravel selon la config :
 *  - config('services.meshy.fake') === true  → FakeMeshyClient
 *  - config('services.meshy.api_key') === null → FakeMeshyClient (sécurité)
 *  - sinon → MeshyHttpClient
 *
 * Toutes les méthodes peuvent lancer une `MeshyException` en cas d'erreur
 * réseau / quota / payload invalide. Les retries / circuit breakers sont
 * implémentés dans `MeshyHttpClient`, pas ici.
 */
interface MeshyClientInterface
{
    /**
     * Lance une nouvelle génération. Retourne l'ID de tâche à poller.
     */
    public function create(MeshyGenerationRequest $request): string;

    /**
     * Lance un refine sur un mesh généré en mode preview. Le refine applique
     * les textures PBR (color, metallic, roughness, normal) qui manquent au
     * mode preview. Retourne un NOUVEAU task_id (différent du preview).
     *
     * Coût Meshy ≈ 10 crédits par refine. À ne déclencher que sur action user.
     */
    public function refine(string $previewTaskId): string;

    /**
     * Récupère le statut courant d'une tâche.
     *
     * $kind discrimine l'endpoint Meshy à interroger :
     *  - 'skin'      → GET /v1/retexture/{id}  (retexture sur mesh existant)
     *  - autres / null → GET /v2/text-to-3d/{id}  (base/weapon/accessory et défaut)
     *
     * Passé optionnellement pour rester rétrocompatible avec les anciens appels.
     */
    public function status(string $taskId, ?string $kind = null): MeshyTaskStatus;

    /**
     * Télécharge un asset distant (le `model_url` ou `texture_url` retourné
     * par Meshy) et renvoie son contenu binaire. Le caller décide où le
     * stocker (Storage::disk('public')->put(...)).
     */
    public function downloadAsset(string $url): string;
}
