<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardReward extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rewards' => 'array',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(LeaderboardSeason::class, 'season_id');
    }
}
