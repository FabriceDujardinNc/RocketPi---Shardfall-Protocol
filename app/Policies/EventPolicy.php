<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, Event $e): bool   { return $user->isAdmin(); }
    public function create(User $user): bool  { return $user->isAdmin(); }
    public function update(User $user, Event $e): bool { return $user->isAdmin(); }
    public function delete(User $user, Event $e): bool { return $user->isAdmin(); }
}
