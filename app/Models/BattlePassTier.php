<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattlePassTier extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'tier_number'    => 'integer',
            'xp_required'    => 'integer',
            'free_reward'    => 'array',
            'premium_reward' => 'array',
            'is_milestone'   => 'boolean',
        ];
    }

    public function battlePass(): BelongsTo
    {
        return $this->belongsTo(BattlePass::class);
    }
}
