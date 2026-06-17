<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function ban(User $user, User $target): bool
    {
        if (! $user->isAdmin()) return false;
        if ($user->id === $target->id) return false;          // pas auto-ban
        if ($target->isSuperAdmin()) return false;            // intouchable
        return true;
    }

    public function promote(User $user, User $target): bool
    {
        if (! $user->isSuperAdmin()) return false;
        if ($user->id === $target->id) return false;          // pas de self-promote
        return true;
    }
}
