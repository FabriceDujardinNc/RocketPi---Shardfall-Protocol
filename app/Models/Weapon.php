<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    use HasAutoSlug, HasFactory;

    protected $guarded = [];

    public const CATEGORIES = ['assault', 'sniper', 'shotgun', 'smg', 'pistol', 'launcher', 'melee'];
    public const RARITIES   = ['common', 'rare', 'epic', 'legendary'];
    public const GENERATION_STATUSES = ['pending', 'queued', 'generating', 'ready', 'failed'];

    protected function casts(): array
    {
        return [
            'stats'     => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function skins(): HasMany
    {
        return $this->hasMany(WeaponSkin::class);
    }

    public function isReady(): bool
    {
        return $this->generation_status === 'ready' && filled($this->base_model_url);
    }
}
