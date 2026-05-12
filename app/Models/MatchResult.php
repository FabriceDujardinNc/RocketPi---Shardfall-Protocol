<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score'              => 'integer',
            'kills'              => 'integer',
            'deaths'             => 'integer',
            'assists'            => 'integer',
            'won'                => 'boolean',
            'is_mvp'             => 'boolean',
            'rank_points_delta'  => 'integer',
            'rank_points_after'  => 'integer',
            'validated_at'       => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchSession::class, 'match_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
