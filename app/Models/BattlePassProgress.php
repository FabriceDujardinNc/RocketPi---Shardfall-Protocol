<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattlePassProgress extends Model
{
    protected $table = 'battle_pass_progress';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_premium'    => 'boolean',
            'xp_earned'     => 'integer',
            'current_tier'  => 'integer',
            'claimed_tiers' => 'array',
            'purchased_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function battlePass(): BelongsTo
    {
        return $this->belongsTo(BattlePass::class);
    }
}
