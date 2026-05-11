<?php

namespace App\Http\Requests\Admin;

use App\Models\BattlePass;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBattlePassTiersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bp = $this->route('battle_pass');
        return $bp instanceof BattlePass
            && $this->user()?->can('update', $bp);
    }

    public function rules(): array
    {
        return [
            'tiers'                     => ['required', 'array', 'min:1', 'max:200'],
            'tiers.*.tier_number'       => ['required', 'integer', 'min:1', 'max:200'],
            'tiers.*.xp_required'       => ['required', 'integer', 'min:0', 'max:10000000'],
            'tiers.*.is_milestone'      => ['boolean'],
            'tiers.*.free_reward'       => ['nullable', 'array', 'max:5'],
            'tiers.*.free_reward.*.type'    => ['required_with:tiers.*.free_reward', 'string', 'max:64'],
            'tiers.*.free_reward.*.amount'  => ['required_with:tiers.*.free_reward', 'integer', 'min:1', 'max:1000000'],
            'tiers.*.premium_reward'    => ['nullable', 'array', 'max:5'],
            'tiers.*.premium_reward.*.type'   => ['required_with:tiers.*.premium_reward', 'string', 'max:64'],
            'tiers.*.premium_reward.*.amount' => ['required_with:tiers.*.premium_reward', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $tiers = $this->input('tiers', []);
            $numbers = array_column($tiers, 'tier_number');
            if (count($numbers) !== count(array_unique($numbers))) {
                $v->errors()->add('tiers', 'Les numéros de palier doivent être uniques.');
            }
        });
    }
}
