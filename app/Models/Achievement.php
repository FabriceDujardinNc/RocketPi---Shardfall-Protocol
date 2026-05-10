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

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
