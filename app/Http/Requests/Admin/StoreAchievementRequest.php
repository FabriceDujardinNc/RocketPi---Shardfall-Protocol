<?php

namespace App\Http\Requests\Admin;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $a = $this->route('achievement');
        if ($a instanceof Achievement) {
            return $this->user()?->can('update', $a) ?? false;
        }
        return $this->user()?->can('create', Achievement::class) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('achievement')?->id;
        return [
            'key'         => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', Rule::unique('achievements', 'key')->ignore($id)],
            'title'       => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon_url'    => ['nullable', 'url', 'max:255'],
            'category'    => ['required', Rule::in(Achievement::CATEGORIES)],
            'is_hidden'   => ['boolean'],
            'rewards'             => ['nullable', 'array', 'max:5'],
            'rewards.*.type'      => ['required_with:rewards', 'string', 'max:64'],
            'rewards.*.amount'    => ['required_with:rewards', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex'   => 'La clé doit être en minuscules, chiffres et underscores uniquement.',
            'key.unique'  => 'Cette clé est déjà utilisée par un autre achievement.',
        ];
    }
}
