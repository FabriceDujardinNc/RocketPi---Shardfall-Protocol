<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerCosmetic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'is_equipped' => 'boolean',
        ];
    }

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function cosmetic(): BelongsTo  { return $this->belongsTo(Cosmetic::class); }
}
