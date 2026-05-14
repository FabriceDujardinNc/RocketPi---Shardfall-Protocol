<?php

namespace App\Services\Meshy\Data;

/**
 * Requête de génération envoyée à Meshy. Type-safe et indépendant des
 * spécificités endpoint Meshy (le client traduit selon `kind`).
 *
 *  - kind="base"      : text-to-3d full mesh (opérateur humanoid)
 *  - kind="skin"      : texture-only (texture du base mesh existant)
 *  - kind="weapon"    : text-to-3d mesh d'arme indépendant
 *  - kind="accessory" : text-to-3d mesh accessoire indépendant
 *
 * On expose volontairement `polycountTarget` et `artStyle` pour figer la
 * cohérence stylistique entre toutes les générations RocketPi.
 */
final readonly class MeshyGenerationRequest
{
    public function __construct(
        public string $kind,                  // base|skin|weapon|accessory
        public string $prompt,
        public ?string $negativePrompt = null,
        public ?int $polycountTarget = null,   // ex: 30_000 pour base
        public string $artStyle = 'realistic',  // realistic|sculpture|toon
        // Pour kind=skin : la texture remplace celle d'un mesh existant.
        // Le client passe le base_model_url cible au backend Meshy.
        public ?string $baseModelUrl = null,
    ) {
    }
}
