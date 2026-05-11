<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $guarded = [];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REWARDED  = 'rewarded';
    public const STATUS_FLAGGED   = 'flagged';

    protected function casts(): array
    {
        return [
            'same_ip_as_referrer' => 'boolean',
            'validated_at'        => 'datetime',
            'flagged_at'          => 'datetime',
            'email_verified_at'   => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }
}
