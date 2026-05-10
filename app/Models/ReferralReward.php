<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralReward extends Model
{
    protected $guarded = [];

    public const TRIGGER_REFEREE_EMAIL_VERIFIED = 'referee_email_verified';
    public const TRIGGER_REFEREE_LEVEL_5        = 'referee_level_5';
    public const TRIGGER_REFEREE_LEVEL_15       = 'referee_level_15';
    public const TRIGGER_REFEREE_LEVEL_30       = 'referee_level_30';
    public const TRIGGER_REFEREE_FIRST_PURCHASE = 'referee_first_purchase';

    protected function casts(): array
    {
        return [
            'reward_amount' => 'integer',
            'claimed'       => 'boolean',
            'claimed_at'    => 'datetime',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_id');
    }
}
