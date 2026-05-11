<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rewards'    => 'array',
            'is_hidden'  => 'boolean',
        ];
    }

    public const CATEGORIES = ['collection', 'combat', 'social', 'progression', 'special'];

    // L'URL admin utilise `key` directement (déjà slug-format unique : first_legendary, reach_50…)
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
