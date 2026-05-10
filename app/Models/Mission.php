<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rewards'         => 'array',
            'is_active'       => 'boolean',
            'available_from'  => 'datetime',
            'available_until' => 'datetime',
        ];
    }
}
