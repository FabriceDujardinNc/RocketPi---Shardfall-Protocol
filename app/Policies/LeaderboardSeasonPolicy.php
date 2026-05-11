<?php

namespace App\Policies;

use App\Models\LeaderboardSeason;
use App\Models\User;

class LeaderboardSeasonPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, LeaderboardSeason $s): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, LeaderboardSeason $s): bool { return $user->isAdmin(); }
    public function delete(User $user, LeaderboardSeason $s): bool
    {
        // Pas de delete d'une saison déjà distribuée — préserve l'audit.
        return $user->isAdmin() && ! $s->rewards_distributed;
    }
}
