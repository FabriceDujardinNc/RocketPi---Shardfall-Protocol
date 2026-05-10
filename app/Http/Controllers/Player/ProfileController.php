<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Profile', [
            'user' => $request->user()->only([
                'id', 'name', 'email', 'display_name', 'avatar_url',
                'account_level', 'account_xp', 'referral_code',
            ]),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('Player/ProfilePublic', [
            'user' => $user->only(['id', 'name', 'display_name', 'avatar_url', 'account_level']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => 'nullable|string|max:50',
            'avatar_url'   => 'nullable|url|max:255',
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profil mis à jour.');
    }
}
