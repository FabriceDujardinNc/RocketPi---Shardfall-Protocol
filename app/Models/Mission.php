<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mission extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rewards'          => 'array',
            'is_active'        => 'boolean',
            'available_from'   => 'datetime',
            'available_until'  => 'datetime',
            'objective_target' => 'integer',
            'xp_reward'        => 'integer',
        ];
    }

    public const TYPES = ['daily', 'weekly', 'event', 'story', 'challenge'];

    public const OBJECTIVE_TYPES = [
        'login',
        'pull',
        'pvp_win',
        'mission_complete',
        'reach_level',
        'spend_currency',
        'open_pack',
    ];

    public const REWARD_TYPES = [
        'shards',
        'credits',
        'tickets_standard',
        'tickets_premium',
        'fragments',
        'tokens_rare_choice',
        'tokens_epic_choice',
        'tokens_legendary_choice',
    ];
}
