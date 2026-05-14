<?php

namespace App\Http\Requests\Api;

use App\Models\Accessory;
use App\Models\Operator;
use App\Models\OperatorSkin;
use App\Models\Weapon;
use Illuminate\Foundation\Http\FormRequest;

class TriggerGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Vérif d'ability faite au niveau du middleware route (ability:mcp:write).
        // On laisse passer si on est arrivé là.
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:operator,operator_skin,weapon,accessory'],
            'slug'        => ['required', 'string', 'max:96'],
            'force'       => ['sometimes', 'boolean'],
        ];
    }

    public function resolveEntity(): \Illuminate\Database\Eloquent\Model
    {
        return match ($this->input('entity_type')) {
            'operator'      => Operator::query()->where('slug', $this->input('slug'))->firstOrFail(),
            'operator_skin' => OperatorSkin::query()->where('slug', $this->input('slug'))->firstOrFail(),
            'weapon'        => Weapon::query()->where('slug', $this->input('slug'))->firstOrFail(),
            'accessory'     => Accessory::query()->where('slug', $this->input('slug'))->firstOrFail(),
        };
    }
}
