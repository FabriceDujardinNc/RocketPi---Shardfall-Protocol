<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorAffinity extends Model
{
    protected $table = 'operator_affinities';
    protected $guarded = [];

    public const MAX_LEVEL = 10;

    protected function casts(): array
    {
        return [
            'level'             => 'integer',
            'xp_current'        => 'integer',
            'unlocked_rewards'  => 'array',
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
