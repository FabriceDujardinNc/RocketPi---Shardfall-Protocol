<?php

namespace App\Http\Resources\Asset3d;

use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation Operator pour le pipeline 3D modulaire (consommé par le
 * serveur MCP + Unity). Distinct de la représentation joueur (PlayerApi
 * controller) — ici on expose les colonnes 3D pertinentes pour
 * l'assemblage runtime.
 *
 * @mixin Operator
 */
class OperatorAsset3dResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'codename'   => $this->codename,
            'name'       => $this->name,
            'faction'    => $this->faction,
            'role'       => $this->role,
            'rarity'     => $this->rarity,
            'base' => [
                'model_url'         => $this->base_model_url,
                'rig_version'       => $this->base_rig_version,
                'generation_status' => $this->base_generation_status,
                'meshy_task_id'     => $this->base_meshy_task_id,
                'is_ready'          => $this->isBaseModelReady(),
            ],
            'skins'       => OperatorSkinResource::collection($this->whenLoaded('skins')),
            'accessories' => AccessoryResource::collection($this->whenLoaded('accessories')),
        ];
    }
}
