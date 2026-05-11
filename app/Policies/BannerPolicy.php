<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function viewAny(User $user): bool  { return $user->isAdmin(); }
    public function view(User $user, Banner $b): bool   { return $user->isAdmin(); }
    public function create(User $user): bool   { return $user->isAdmin(); }
    public function update(User $user, Banner $b): bool { return $user->isAdmin(); }
    public function delete(User $user, Banner $b): bool { return $user->isAdmin(); }
    public function restore(User $user, Banner $b): bool { return $user->isAdmin(); }
    public function activate(User $user, Banner $b): bool { return $user->isAdmin(); }
}
