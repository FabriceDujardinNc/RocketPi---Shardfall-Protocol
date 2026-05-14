<?php

namespace App\Http\Resources\Asset3d;

use App\Models\Weapon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Weapon */
class WeaponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'slug'              => $this->slug,
            'name'              => $this->name,
            'category'          => $this->category,
            'rarity'            => $this->rarity,
            'base_model_url'    => $this->base_model_url,
            'socket_name'       => $this->socket_name,
            'stats'             => $this->stats,
            'generation_status' => $this->generation_status,
            'meshy_task_id'     => $this->meshy_task_id,
            'is_active'         => $this->is_active,
            'is_ready'          => $this->isReady(),
            'skins'             => WeaponSkinResource::collection($this->whenLoaded('skins')),
        ];
    }
}
