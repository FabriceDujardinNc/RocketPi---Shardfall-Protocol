<?php

namespace App\Services\Meshy;

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use App\Services\Meshy\Data\MeshyGenerationRequest;
use Illuminate\Support\Facades\Storage;

/**
 * Construit les prompts Meshy à partir d'entités Eloquent.
 *
 * **Cohérence stylistique** : tous les prompts héritent d'un préfixe et
 * d'un negative_prompt communs (`STYLE_PREFIX`, `STYLE_NEGATIVE`) pour
 * limiter la dérive visuelle entre générations indépendantes.
 *
 * Univers RocketPi: Shardfall Protocol = 2087, opérateurs PMC futuristes,
 * armures composites, palette froide cyan/cyan-pourpre côté ORBIT, rouille
 * et acier côté FERRO, violet/noir côté VEIL.
 */
class MeshyPromptBuilder
{
    // T-pose strict : indispensable pour passer dans Mixamo Auto-Rigger
    // (le rigger échoue si bras le long du corps ou pose action).
    // L'ordre des termes compte : Meshy donne plus de poids au début + fin du prompt.
    private const STYLE_PREFIX = 'T-pose character, arms fully extended horizontally to the sides, palms open facing down, '
        . 'legs straight slightly apart, standing upright on flat ground, empty hands, no weapons, no accessories held, '
        . 'futuristic sci-fi PMC operative, year 2087, hero-shooter game-ready character, '
        . 'neutral facial expression looking forward, balanced symmetric anatomy, AAA stylized realism, ';

    // Suffixe rappelé en fin de prompt pour renforcer la T-pose (Meshy biaise vers les
    // derniers mots du prompt).
    private const STYLE_SUFFIX = ' Strict T-pose, arms horizontal, empty hands, ready for Mixamo Auto-Rigger.';

    private const STYLE_NEGATIVE = 'cartoon, anime, low quality, deformed, extra limbs, watermark, text, logo, '
        . 'photorealistic skin pores, nsfw, blood, gore, asymmetric anatomy, '
        . 'holding weapon, gun in hand, rifle in hand, pistol in hand, knife in hand, '
        . 'arms down, arms at sides, arms crossed, hands on hips, hands in pockets, hands clenched, '
        . 'action pose, dynamic pose, combat stance, crouching, kneeling, running, walking, aiming, '
        . 'A-pose, contrapposto, fashion pose, hero pose';

    private const FACTION_FLAVOR = [
        'ORBIT' => 'cyan and white composite armor, orbital infantry, clean carbon weave, '
            . 'subtle holographic accents, helmet HUD visor',
        'FERRO' => 'rust-and-steel salvage armor, industrial PMC, exposed bolts and plating, '
            . 'heavy gauntlets, weathered metal',
        'VEIL'  => 'dark purple and black stealth suit, covert infiltrator, matte fabric, '
            . 'soft hood, minimal reflective surfaces',
    ];

    public function forOperatorBase(Operator $operator): MeshyGenerationRequest
    {
        $faction = self::FACTION_FLAVOR[$operator->faction] ?? '';
        $role    = $this->describeRoleVisual($operator->role);
        // Lore court (100 chars max) pour ambiance, sans verbes d'action qui
        // pourraient déclencher une pose dynamique (sniper "scanning horizon" → mesh debout
        // avec fusil épaulé). Voir describeRoleVisual qui supprime les références aux armes.
        $lore = $operator->lore ? mb_strimwidth($operator->lore, 0, 100, '…') : '';

        $prompt = self::STYLE_PREFIX
            . "{$operator->name} ({$operator->codename}), {$role}, {$faction}. {$lore}"
            . self::STYLE_SUFFIX;

        return new MeshyGenerationRequest(
            kind: 'base',
            prompt: trim($prompt),
            negativePrompt: self::STYLE_NEGATIVE,
            polycountTarget: 30_000,
            artStyle: 'realistic',
        );
    }

    public function forOperatorSkin(OperatorSkin $skin): MeshyGenerationRequest
    {
        $operator = $skin->operator;
        $palette = $this->describePalette($skin->palette_json);
        $faction = self::FACTION_FLAVOR[$operator->faction] ?? '';

        // Endpoint Meshy /v1/retexture limite text_style_prompt à 600 chars
        // ET ne supporte pas negative_prompt. On envoie un prompt court ciblé
        // sur la palette/material, sans le préfixe stylistique full.
        $prompt = "skin variant for {$operator->name}: {$skin->name}. "
            . "{$palette} Faction style: {$faction}. "
            . 'AAA stylized realism, clean material work, no nsfw.';

        // Meshy /v1/retexture exige une URL absolue http(s) accessible publiquement.
        // Storage::disk('public')->url() préfixe APP_URL (https://rocketpi.pro/storage/...).
        $absoluteModelUrl = $operator->base_model_url
            ? Storage::disk('public')->url($operator->base_model_url)
            : null;

        return new MeshyGenerationRequest(
            kind: 'skin',
            prompt: mb_substr(trim($prompt), 0, 600),
            negativePrompt: self::STYLE_NEGATIVE,
            polycountTarget: null,
            artStyle: 'realistic',
            baseModelUrl: $absoluteModelUrl,
        );
    }

    public function forWeapon(Weapon $weapon): MeshyGenerationRequest
    {
        $category = $weapon->category;
        $prompt = 'futuristic sci-fi 2087 PMC weapon, game-ready 3D model, neutral orientation, '
            . "category {$category}, {$weapon->name}, modular rail attachments, "
            . 'cyan and dark grey accents, clean low-poly hero asset';

        return new MeshyGenerationRequest(
            kind: 'weapon',
            prompt: $prompt,
            negativePrompt: 'cartoon, low quality, deformed, watermark, text, modern day rifle, realistic AK47',
            polycountTarget: 12_000,
            artStyle: 'realistic',
        );
    }

    public function forAccessory(Accessory $accessory): MeshyGenerationRequest
    {
        $slot = $accessory->slot;
        $prompt = 'futuristic sci-fi 2087 PMC accessory, game-ready 3D model, neutral orientation, '
            . "{$slot} slot, {$accessory->name}, modular attachment, hard-surface, clean topology";

        return new MeshyGenerationRequest(
            kind: 'accessory',
            prompt: $prompt,
            negativePrompt: 'cartoon, low quality, deformed, watermark, text, soft cloth, fabric folds',
            polycountTarget: 6_000,
            artStyle: 'realistic',
        );
    }

    private function describeRole(string $role): string
    {
        return match ($role) {
            'sniper'      => 'long-range marksman with ghillie elements',
            'healer'      => 'support medic with med-pack rig',
            'scout'       => 'lightweight recon operative',
            'tank'        => 'heavy armored frontline',
            'explosives'  => 'demolitions operative with grenade harness',
            'assault'     => 'frontline assault operator',
            'infiltrator' => 'stealth infiltrator in low-profile suit',
            'hacker'      => 'electronic warfare operator with arm-mounted deck',
            default       => 'PMC operative',
        };
    }

    /**
     * Variante de describeRole utilisée pour la génération base T-pose :
     * supprime tout ce qui pourrait suggérer une arme en main, une pose
     * d'action ou un accessoire tenu. La silhouette doit rester strictement
     * neutre et compatible Mixamo Auto-Rigger.
     */
    private function describeRoleVisual(string $role): string
    {
        return match ($role) {
            'sniper'      => 'tall marksman silhouette with ghillie-style fabric accents on shoulders',
            'healer'      => 'medic with utility vest and shoulder-mounted med-pack housing',
            'scout'       => 'lightweight recon operative with slim armor plates',
            'tank'        => 'heavy armored frontline operative, broad shoulders and chest plate',
            'explosives'  => 'demolitions operative with empty grenade harness on chest',
            'assault'     => 'standard assault operator with chest rig',
            'infiltrator' => 'stealth operative in low-profile suit with hood',
            'hacker'      => 'electronic warfare operator with arm-mounted deck device',
            default       => 'PMC operative with neutral chest rig',
        };
    }

    private function describePalette(mixed $paletteJson): string
    {
        if (! is_array($paletteJson) || empty($paletteJson)) {
            return 'use accent colors aligned with operator faction.';
        }

        $parts = [];
        foreach ($paletteJson as $entry) {
            if (! is_array($entry)) continue;
            $slot = $entry['slot'] ?? null;
            $hex  = $entry['hex']  ?? null;
            if ($slot && $hex) {
                $parts[] = "{$slot}: {$hex}";
            }
        }

        return $parts
            ? 'palette: ' . implode(', ', $parts) . '.'
            : 'use accent colors aligned with operator faction.';
    }
}
