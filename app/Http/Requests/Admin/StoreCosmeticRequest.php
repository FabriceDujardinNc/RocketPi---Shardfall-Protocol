<?php

namespace App\Http\Requests\Admin;

use App\Models\Cosmetic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCosmeticRequest extends FormRequest
{
    public function authorize(): bool
    {
        $c = $this->route('cosmetic');
        if ($c instanceof Cosmetic) {
            return $this->user()?->can('update', $c) ?? false;
        }
        return $this->user()?->can('create', Cosmetic::class) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('cosmetic')?->id;
        return [
            'slug'        => ['required', 'string', 'max:96', 'regex:/^[a-z0-9_-]+$/', Rule::unique('cosmetics', 'slug')->ignore($id)],
            'name'        => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type'        => ['required', Rule::in(Cosmetic::TYPES)],
            'rarity'      => ['required', Rule::in(Cosmetic::RARITIES)],
            'operator_id' => ['nullable', 'integer', 'exists:operators,id'],
            'preview_url' => ['nullable', 'url', 'max:255'],
            'asset_url'   => ['nullable', 'url', 'max:255'],
            'is_active'   => ['boolean'],
            'metadata'    => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Un skin DOIT avoir un opérateur (par définition c'est une variante visuelle d'un op).
            if ($this->input('type') === 'skin' && ! $this->filled('operator_id')) {
                $v->errors()->add('operator_id', 'Un skin doit être lié à un opérateur.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug : minuscules, chiffres, underscores et tirets uniquement.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
        ];
    }
}
