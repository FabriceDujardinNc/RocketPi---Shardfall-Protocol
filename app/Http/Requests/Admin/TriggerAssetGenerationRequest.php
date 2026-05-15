<?php

namespace App\Http\Requests\Admin;

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class TriggerAssetGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-content') ?? false;
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|in:operator,operator_skin,weapon,accessory',
            'entity_slug' => 'required|string|max:120',
            'force'       => 'sometimes|boolean',
        ];
    }

    public function resolveEntity(): Model
    {
        return match ($this->input('entity_type')) {
            'operator'      => Operator::query()->where('slug', $this->input('entity_slug'))->firstOrFail(),
            'operator_skin' => OperatorSkin::query()->where('slug', $this->input('entity_slug'))->firstOrFail(),
            'weapon'        => Weapon::query()->where('slug', $this->input('entity_slug'))->firstOrFail(),
            'accessory'     => Accessory::query()->where('slug', $this->input('entity_slug'))->firstOrFail(),
        };
    }
}
