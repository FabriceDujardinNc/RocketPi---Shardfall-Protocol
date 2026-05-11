<?php

namespace App\Http\Requests\Admin;

use App\Models\DailyLoginReward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyLoginRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reward = $this->route('daily_login_reward');
        if ($reward instanceof DailyLoginReward) {
            return $this->user()?->can('update', $reward) ?? false;
        }
        return $this->user()?->can('create', DailyLoginReward::class) ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('daily_login_reward')?->id;
        return [
            'day_number'        => ['required', 'integer', 'min:1', 'max:365', Rule::unique('daily_login_rewards', 'day_number')->ignore($id)],
            'label'             => ['nullable', 'string', 'max:64'],
            'is_milestone'      => ['boolean'],
            'rewards'           => ['required', 'array', 'min:1', 'max:5'],
            'rewards.*.type'    => ['required', 'string', 'max:64'],
            'rewards.*.amount'  => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_number.unique' => 'Ce jour a déjà une configuration — édite la ligne existante.',
        ];
    }
}
