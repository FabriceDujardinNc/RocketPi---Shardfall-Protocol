<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerOperator extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'duplicate_count' => 'integer',
            'constellation'   => 'integer',
            'is_favorite'     => 'boolean',
            'obtained_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
}
