<?php

namespace App\Http\Resources\Asset3d;

use App\Models\WeaponSkin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WeaponSkin */
class WeaponSkinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'name'               => $this->name,
            'rarity'             => $this->rarity,
            'texture_url'        => $this->texture_url,
            'material_overrides' => $this->material_overrides,
            'preview_url'        => $this->preview_url,
            'generation_status'  => $this->generation_status,
            'meshy_task_id'      => $this->meshy_task_id,
            'is_active'          => $this->is_active,
            'is_default'         => $this->is_default,
            'is_ready'           => $this->isReady(),
        ];
    }
}
