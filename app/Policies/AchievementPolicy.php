<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, Achievement $a): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, Achievement $a): bool { return $user->isAdmin(); }
    public function delete(User $user, Achievement $a): bool { return $user->isAdmin(); }
}
