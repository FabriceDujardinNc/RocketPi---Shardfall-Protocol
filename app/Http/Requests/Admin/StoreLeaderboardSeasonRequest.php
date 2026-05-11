<?php

namespace App\Http\Requests\Admin;

use App\Models\LeaderboardSeason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaderboardSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $s = $this->route('season');
        if ($s instanceof LeaderboardSeason) {
            return $this->user()?->can('update', $s) ?? false;
        }
        return $this->user()?->can('create', LeaderboardSeason::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:64'],
            'type'          => ['required', Rule::in(['weekly', 'monthly', 'seasonal', 'annual', 'collection', 'faction'])],
            'faction'       => ['nullable', 'required_if:type,faction', Rule::in(['ORBIT', 'FERRO', 'VEIL'])],
            'season_number' => ['required', 'integer', 'min:1', 'max:65535'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['required', 'date', 'after:starts_at'],
            'is_active'     => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'faction.required_if' => 'Le slug de faction (ORBIT/FERRO/VEIL) est requis pour les saisons de type faction.',
        ];
    }
}
