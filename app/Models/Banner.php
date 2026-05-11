<?php

namespace App\Models;

use App\Concerns\HasAutoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes, HasAutoSlug;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate_up_operators' => 'array',
            'rate_legendary'    => 'decimal:4',
            'rate_epic'         => 'decimal:4',
            'rate_rare'         => 'decimal:4',
            'rate_common'       => 'decimal:4',
            'starts_at'         => 'datetime',
            'ends_at'           => 'datetime',
            'is_active'         => 'boolean',
            'pity_legendary'    => 'integer',
            'soft_pity_start'   => 'integer',
            'pity_epic'         => 'integer',
        ];
    }

    public const TYPES = ['permanent', 'event', 'faction', 'collab'];
}
