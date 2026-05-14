<?php

namespace App\Http\Resources\Asset3d;

use App\Models\Accessory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Accessory */
class AccessoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'slug'              => $this->slug,
            'name'              => $this->name,
            'slot'              => $this->slot,
            'rarity'            => $this->rarity,
            'base_model_url'    => $this->base_model_url,
            'socket_name'       => $this->socket_name,
            'preview_url'       => $this->preview_url,
            'generation_status' => $this->generation_status,
            'meshy_task_id'     => $this->meshy_task_id,
            'is_active'         => $this->is_active,
            'is_default'        => $this->whenPivotLoaded('operator_accessories', fn () => (bool) $this->pivot->is_default),
            'is_ready'          => $this->isReady(),
        ];
    }
}
