<?php

namespace App\Policies;

use App\Models\Operator;
use App\Models\User;

class OperatorPolicy
{
    public function viewAny(User $user): bool   { return $user->isAdmin(); }
    public function view(User $user, Operator $op): bool   { return $user->isAdmin(); }
    public function create(User $user): bool    { return $user->isAdmin(); }
    public function update(User $user, Operator $op): bool { return $user->isAdmin(); }
    public function delete(User $user, Operator $op): bool { return $user->isAdmin(); }
    public function restore(User $user, Operator $op): bool { return $user->isAdmin(); }
}
