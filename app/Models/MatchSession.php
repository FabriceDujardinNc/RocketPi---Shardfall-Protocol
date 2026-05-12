<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at'       => 'datetime',
            'finished_at'      => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public const MODES      = ['deathmatch', 'pve', 'custom', 'training'];
    public const RANK_TYPES = ['ranked', 'casual'];
    public const STATUSES   = ['started', 'finished', 'abandoned', 'invalidated'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_used_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }
}
