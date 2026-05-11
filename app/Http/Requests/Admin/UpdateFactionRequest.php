<?php

namespace App\Http\Requests\Admin;

use App\Models\Faction;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $f = $this->route('faction');
        return $f instanceof Faction && ($this->user()?->can('update', $f) ?? false);
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:64'],
            'tagline'          => ['nullable', 'string', 'max:255'],
            'lore'             => ['nullable', 'string', 'max:5000'],
            'color_hue'        => ['required', 'integer', 'min:0', 'max:360'],
            'accent_class'     => ['nullable', 'string', 'max:32'],
            'banner_image_url' => ['nullable', 'url', 'max:255'],
            'icon_url'         => ['nullable', 'url', 'max:255'],
        ];
    }
}
