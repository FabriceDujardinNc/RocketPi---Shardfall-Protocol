<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'abilities'    => 'array',
            'is_available' => 'boolean',
            'is_rate_up'   => 'boolean',
        ];
    }
}
