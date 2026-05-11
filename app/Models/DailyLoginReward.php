<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLoginReward extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'day_number'   => 'integer',
            'rewards'      => 'array',
            'is_milestone' => 'boolean',
        ];
    }
}
