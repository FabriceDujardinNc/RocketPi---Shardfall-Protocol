<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PityCounter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'legendary_counter' => 'integer',
            'epic_counter'      => 'integer',
            'total_pulls'       => 'integer',
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
}
