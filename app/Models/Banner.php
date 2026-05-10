<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
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
        ];
    }
}
