<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score'                  => 'integer',
            'rank'                   => 'integer',
            'games_played'           => 'integer',
            'wins'                   => 'integer',
            'daily_score_earned'     => 'integer',
            'daily_score_reset_date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(LeaderboardSeason::class, 'season_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
