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
                'id', 'name', 'email', 'role', 'account_level',
                'is_banned', 'ban_reason', 'banned_at',
                'created_at', 'last_active_at', 'referral_code',
            ]),
        ]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];

        $user->update([
            'is_banned' => true,
            'ban_reason' => $reason,
            'banned_at' => now(),
        ]);

        return back()->with('status', 'Joueur banni.');
    }

    public function unban(User $user): RedirectResponse
    {
        $user->update([
            'is_banned' => false,
            'ban_reason' => null,
            'banned_at' => null,
        ]);

        return back()->with('status', 'Bannissement levé.');
    }

    public function grantCurrency(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'currency' => 'required|in:premium,soft,fragments',
            'amount'   => 'required|integer|min:1|max:100000',
            'reason'   => 'required|string|max:255',
        ]);

        // TODO Phase 1 — implémentation atomique avec log admin
        return back()->with('status', 'Monnaie créditée (stub).');
    }
}
