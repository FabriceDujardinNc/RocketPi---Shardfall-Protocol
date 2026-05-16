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
    private const STYLE_PREFIX = 'futuristic sci-fi PMC operative, year 2087, hero-shooter game-ready character, '
        . 'A-pose, neutral facial expression, balanced proportions, AAA stylized realism, ';

    private const STYLE_NEGATIVE = 'cartoon, anime, low quality, deformed, extra limbs, watermark, text, logo, '
        . 'photorealistic skin pores, nsfw, blood, gore, asymmetric anatomy';

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
        $role    = $this->describeRole($operator->role);
        $lore    = $operator->lore ? mb_strimwidth($operator->lore, 0, 200, '…') : '';

        $prompt = self::STYLE_PREFIX
            . "{$operator->name} ({$operator->codename}), {$role}, {$faction}. {$lore}";

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
