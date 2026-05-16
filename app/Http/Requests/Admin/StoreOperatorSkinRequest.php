<?php

namespace App\Http\Requests\Admin;

use App\Models\OperatorSkin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorSkinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-content') ?? false;
    }

    public function rules(): array
    {
        $skinId = $this->route('skin')?->id;

        return [
            'operator_id'              => ['required', 'integer', 'exists:operators,id'],
            'name'                     => ['required', 'string', 'max:128'],
            'slug'                     => ['nullable', 'string', 'max:96', Rule::unique('operator_skins', 'slug')->ignore($skinId)],
            'rarity'                   => ['required', Rule::in(OperatorSkin::RARITIES)],
            'palette_json'             => ['nullable', 'array', 'max:8'],
            'palette_json.*.slot'      => ['required_with:palette_json', 'string', 'max:32'],
            'palette_json.*.hex'       => ['required_with:palette_json', 'string', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'is_active'                => ['boolean'],
            'is_default'               => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'palette_json.*.hex.regex' => 'Chaque palette.hex doit être un code couleur (#RGB, #RRGGBB ou #RRGGBBAA).',
        ];
    }
}
