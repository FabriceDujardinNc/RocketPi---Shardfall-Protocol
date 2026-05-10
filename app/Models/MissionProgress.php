<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionProgress extends Model
{
    protected $table = 'mission_progress';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'progress'       => 'integer',
            'completed'      => 'boolean',
            'reward_claimed' => 'boolean',
            'completed_at'   => 'datetime',
            'claimed_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
