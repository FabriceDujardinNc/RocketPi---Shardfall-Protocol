<?php

namespace App\Policies;

use App\Models\Faction;
use App\Models\User;

class FactionPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, Faction $f): bool   { return $user->isAdmin(); }
    public function update(User $user, Faction $f): bool { return $user->isAdmin(); }
}
