<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit légal des tirages gacha. Immuable, conservée indéfiniment
 * pour conformité loot box transparency.
 */
class GachaPull extends Model
{
    protected $guarded = [];
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'pity_count_before' => 'integer',
            'cost'              => 'integer',
            'was_pity_hit'      => 'boolean',
            'was_soft_pity'     => 'boolean',
            'was_rate_up'       => 'boolean',
            'created_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
}
