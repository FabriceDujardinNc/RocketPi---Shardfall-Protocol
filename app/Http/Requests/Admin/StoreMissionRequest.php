<?php

namespace App\Http\Requests\Admin;

use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Mission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:128'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'type'             => ['required', Rule::in(Mission::TYPES)],
            'objective_type'   => ['required', Rule::in(Mission::OBJECTIVE_TYPES)],
            'objective_target' => ['required', 'integer', 'min:1', 'max:1000000'],
            'rewards'              => ['required', 'array', 'min:1', 'max:5'],
            'rewards.*.type'       => ['required', 'string', 'max:64'],
            'rewards.*.amount'     => ['required', 'integer', 'min:1', 'max:1000000'],
            'xp_reward'        => ['integer', 'min:0', 'max:65535'],
            'is_active'        => ['boolean'],
            'available_from'   => ['nullable', 'date'],
            'available_until'  => ['nullable', 'date', 'after_or_equal:available_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'available_until.after_or_equal' => 'La date de fin doit être après la date de début.',
        ];
    }
}
