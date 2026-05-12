<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerReport extends Model
{
    protected $guarded = [];

    public const REASONS  = ['cheat', 'toxic', 'afk', 'smurf', 'other'];
    public const STATUSES = ['pending', 'reviewed', 'dismissed', 'sanctioned'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MatchSession::class, 'match_session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
