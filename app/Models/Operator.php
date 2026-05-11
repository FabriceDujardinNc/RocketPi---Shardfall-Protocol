<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'abilities'     => 'array',
            'is_available'  => 'boolean',
            'is_rate_up'    => 'boolean',
            'stat_hp'       => 'integer',
            'stat_damage'   => 'integer',
            'stat_mobility' => 'integer',
            'sort_order'    => 'integer',
        ];
    }

    public const FACTIONS = ['ORBIT', 'FERRO', 'VEIL'];
    public const ROLES    = ['sniper', 'healer', 'scout', 'tank', 'explosives', 'assault', 'infiltrator', 'hacker'];
    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];
}
