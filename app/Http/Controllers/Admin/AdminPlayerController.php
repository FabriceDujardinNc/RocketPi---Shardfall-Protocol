<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlayerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();

        $players = User::query()
            ->when($search, fn($q) => $q->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Players/Index', [
            'players' => $players,
            'q' => $search,
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Players/Show', [
            'user' => $user->only([
                'id', 'name', 'email', 'role',
                'is_banned', 'ban_reason', 'banned_at',
                'created_at', 'last_active_at',
            ]),
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $this->authorize('ban', $user);

        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];

        $user->update([
            'is_banned'  => true,
            'ban_reason' => $reason,
            'banned_at'  => now(),
        ]);

        return back()->with('status', 'Joueur banni.');
    }

    public function unban(User $user): RedirectResponse
    {
        $this->authorize('ban', $user);

        $user->update([
            'is_banned'  => false,
            'ban_reason' => null,
            'banned_at'  => null,
        ]);

        return back()->with('status', 'Bannissement levé.');
    }
}
