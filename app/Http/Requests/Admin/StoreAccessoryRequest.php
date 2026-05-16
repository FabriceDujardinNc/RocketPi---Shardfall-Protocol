<?php

namespace App\Http\Requests\Admin;

use App\Models\Accessory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-content') ?? false;
    }

    public function rules(): array
    {
        $accessoryId = $this->route('accessory')?->id;

        return [
            'name'              => ['required', 'string', 'max:128'],
            'slug'              => ['nullable', 'string', 'max:96', Rule::unique('accessories', 'slug')->ignore($accessoryId)],
            'slot'              => ['required', Rule::in(Accessory::SLOTS)],
            'rarity'            => ['required', Rule::in(Accessory::RARITIES)],
            'socket_name'       => ['required', 'string', 'max:32'],
            'is_active'         => ['boolean'],
            'operator_ids'      => ['nullable', 'array'],
            'operator_ids.*'    => ['integer', 'exists:operators,id'],
            'default_operator_id' => ['nullable', 'integer', 'exists:operators,id'],
        ];
    }
}
