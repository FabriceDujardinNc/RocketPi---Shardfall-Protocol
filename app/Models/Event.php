<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasAutoSlug;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rewards_pool' => 'array',
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
            'is_active'    => 'boolean',
        ];
    }

    public const TYPES = ['limited_banner', 'pvp_mode', 'pve_mode', 'story', 'collaboration'];

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
