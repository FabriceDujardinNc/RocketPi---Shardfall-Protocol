<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardSeason extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at'             => 'datetime',
            'ends_at'               => 'datetime',
            'is_active'             => 'boolean',
            'rewards_distributed'   => 'boolean',
            'daily_score_reset_date' => 'date',
        ];
    }
}
