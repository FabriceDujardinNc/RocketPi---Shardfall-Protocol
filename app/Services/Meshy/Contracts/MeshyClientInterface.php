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
     * Récupère le statut courant d'une tâche.
     */
    public function status(string $taskId): MeshyTaskStatus;

    /**
     * Télécharge un asset distant (le `model_url` ou `texture_url` retourné
     * par Meshy) et renvoie son contenu binaire. Le caller décide où le
     * stocker (Storage::disk('public')->put(...)).
     */
    public function downloadAsset(string $url): string;
}
