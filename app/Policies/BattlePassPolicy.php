<?php

namespace App\Policies;

use App\Models\BattlePass;
use App\Models\User;

class BattlePassPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, BattlePass $bp): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, BattlePass $bp): bool { return $user->isAdmin(); }
    public function delete(User $user, BattlePass $bp): bool { return $user->isAdmin(); }
}
