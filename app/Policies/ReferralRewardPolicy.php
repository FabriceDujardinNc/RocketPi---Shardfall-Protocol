<?php

namespace App\Policies;

use App\Models\ReferralReward;
use App\Models\User;

class ReferralRewardPolicy
{
    public function claim(User $user, ReferralReward $reward): bool
    {
        return $user->id === (int) $reward->beneficiary_id
            && ! $reward->claimed;
    }
}
