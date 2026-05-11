<?php

namespace App\Policies;

use App\Models\DailyLoginReward;
use App\Models\User;

class DailyLoginRewardPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, DailyLoginReward $r): bool { return $user->isAdmin(); }
    public function delete(User $user, DailyLoginReward $r): bool { return $user->isAdmin(); }
}
