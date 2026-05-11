<?php

namespace App\Http\Requests\Admin;

use App\Models\Operator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Operator::class) ?? false;
    }

    public function rules(): array
    {
        $operatorId = $this->route('operator')?->id;

        return [
            'name'               => ['required', 'string', 'max:32', Rule::unique('operators', 'name')->ignore($operatorId)],
            'codename'           => ['required', 'string', 'max:16', Rule::unique('operators', 'codename')->ignore($operatorId)],
            'faction'            => ['required', Rule::in(Operator::FACTIONS)],
            'role'               => ['required', Rule::in(Operator::ROLES)],
            'rarity'             => ['required', Rule::in(Operator::RARITIES)],
            'lore'               => ['nullable', 'string', 'max:5000'],
            'portrait_url'       => ['nullable', 'url', 'max:255'],
            'stat_hp'            => ['required', 'integer', 'min:1',  'max:65535'],
            'stat_damage'        => ['required', 'integer', 'min:1',  'max:65535'],
            'stat_mobility'      => ['required', 'integer', 'min:1',  'max:65535'],
            'weapon_name'        => ['nullable', 'string', 'max:64'],
            'weapon_description' => ['nullable', 'string', 'max:2000'],
            'abilities'                  => ['nullable', 'array', 'max:10'],
            'abilities.*.name'           => ['required_with:abilities', 'string', 'max:64'],
            'abilities.*.type'           => ['required_with:abilities', Rule::in(['active', 'passive', 'ultimate'])],
            'abilities.*.description'    => ['required_with:abilities', 'string', 'max:1000'],
            'lore_unlocks'               => ['nullable', 'array', 'max:5'],
            'lore_unlocks.*.level'       => ['required_with:lore_unlocks', 'integer', Rule::in(Operator::LORE_UNLOCK_LEVELS)],
            'lore_unlocks.*.title'       => ['required_with:lore_unlocks', 'string', 'max:64'],
            'lore_unlocks.*.snippet'     => ['nullable', 'string', 'max:3000'],
            'is_available'       => ['boolean'],
            'is_rate_up'         => ['boolean'],
            'sort_order'         => ['integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'     => 'Un opérateur porte déjà ce nom.',
            'codename.unique' => 'Ce codename est déjà utilisé.',
            'abilities.max'   => 'Maximum 10 capacités par opérateur.',
        ];
    }
}
