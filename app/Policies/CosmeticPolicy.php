<?php

namespace App\Policies;

use App\Models\Cosmetic;
use App\Models\User;

class CosmeticPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, Cosmetic $c): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, Cosmetic $c): bool { return $user->isAdmin(); }
    public function delete(User $user, Cosmetic $c): bool { return $user->isAdmin(); }
}
