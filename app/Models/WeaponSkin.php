<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeaponSkin extends Model
{
    use HasAutoSlug, HasFactory;

    protected $guarded = [];

    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];
    public const GENERATION_STATUSES = ['pending', 'queued', 'generating', 'ready', 'failed'];

    protected function casts(): array
    {
        return [
            'material_overrides' => 'array',
            'is_active'          => 'boolean',
            'is_default'         => 'boolean',
        ];
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function isReady(): bool
    {
        return $this->generation_status === 'ready' && filled($this->texture_url);
    }
}
