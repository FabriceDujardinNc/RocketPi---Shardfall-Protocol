<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\User;

class MissionPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, Mission $m): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, Mission $m): bool { return $user->isAdmin(); }
    public function delete(User $user, Mission $m): bool { return $user->isAdmin(); }
    public function restore(User $user, Mission $m): bool { return $user->isAdmin(); }
}
