<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Banner::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:64'],
            'tag'               => ['nullable', 'string', 'max:64'],
            'subtitle'          => ['nullable', 'string', 'max:128'],
            'type'              => ['required', Rule::in(Banner::TYPES)],
            'featured_operator' => ['nullable', 'string', 'max:16', 'exists:operators,codename'],
            'rate_up_operators'    => ['nullable', 'array', 'max:5'],
            'rate_up_operators.*'  => ['string', 'max:16', 'exists:operators,codename'],
            'banner_image_url'  => ['nullable', 'url', 'max:255'],
            'rate_legendary'    => ['required', 'numeric', 'min:0', 'max:1'],
            'rate_epic'         => ['required', 'numeric', 'min:0', 'max:1'],
            'rate_rare'         => ['required', 'numeric', 'min:0', 'max:1'],
            'rate_common'       => ['required', 'numeric', 'min:0', 'max:1'],
            'pity_legendary'    => ['required', 'integer', 'min:1',  'max:200'],
            'soft_pity_start'   => ['required', 'integer', 'min:1',  'max:200'],
            'pity_epic'         => ['required', 'integer', 'min:1',  'max:50'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date', 'after:starts_at'],
            'is_active'         => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $sum = $this->float('rate_legendary') + $this->float('rate_epic')
                 + $this->float('rate_rare')      + $this->float('rate_common');
            if (abs($sum - 1.0) > 0.0001) {
                $v->errors()->add('rate_legendary', "La somme des taux doit faire 1.0000 (actuel : {$sum}).");
            }
            if ($this->integer('soft_pity_start') > $this->integer('pity_legendary')) {
                $v->errors()->add('soft_pity_start', 'Le soft pity doit démarrer avant le hard pity.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'featured_operator.exists'   => 'Codename opérateur inconnu.',
            'rate_up_operators.*.exists' => 'Un des codenames rate-up est inconnu.',
            'ends_at.after'              => 'La date de fin doit être après la date de début.',
        ];
    }
}
