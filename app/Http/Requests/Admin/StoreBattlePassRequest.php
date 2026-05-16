<?php

namespace App\Http\Requests\Admin;

use App\Models\BattlePass;
use Illuminate\Foundation\Http\FormRequest;

class StoreBattlePassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BattlePass::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:64'],
            'season_number'        => ['required', 'integer', 'min:1', 'max:65535'],
            'total_tiers'          => ['required', 'integer', 'min:1', 'max:200'],
            'premium_price_shards'  => ['required', 'integer', 'min:0', 'max:1000000'],
            'premium_price_tickets' => ['required', 'integer', 'min:0', 'max:65535'],
            'starts_at'            => ['required', 'date'],
            'ends_at'              => ['required', 'date', 'after:starts_at'],
            'is_active'            => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Anti-overlap : aucun chevauchement avec une autre saison.
            // Critère : starts_at < other.ends_at AND other.starts_at < ends_at.
            $startsAt = $this->date('starts_at');
            $endsAt   = $this->date('ends_at');
            if (! $startsAt || ! $endsAt) {
                return;
            }

            $bpId = $this->route('battle_pass')?->id;
            $exists = BattlePass::query()
                ->when($bpId, fn ($q) => $q->where('id', '!=', $bpId))
                ->where('starts_at', '<',  $endsAt)
                ->where('ends_at',   '>',  $startsAt)
                ->exists();

            if ($exists) {
                $v->errors()->add(
                    'starts_at',
                    'Une autre saison Battle Pass chevauche cette période. Ajuste les dates pour éviter le conflit.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'La date de fin doit être après la date de début.',
        ];
    }
}
