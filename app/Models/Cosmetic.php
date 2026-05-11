<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cosmetic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata'  => 'array',
            'is_active' => 'boolean',
        ];
    }

    public const TYPES    = ['skin', 'title', 'voiceline', 'banner', 'border'];
    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function unlockedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_cosmetics')
            ->withPivot('unlocked_at', 'source', 'is_equipped')
            ->withTimestamps();
    }
}
