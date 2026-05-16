<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BattlePass extends Model
{
    use HasAutoSlug;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'season_number'        => 'integer',
            'total_tiers'          => 'integer',
            'premium_price_shards'  => 'integer',
            'premium_price_tickets' => 'integer',
            'starts_at'            => 'datetime',
            'ends_at'              => 'datetime',
            'is_active'            => 'boolean',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(BattlePassTier::class)->orderBy('tier_number');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(BattlePassProgress::class);
    }
}
