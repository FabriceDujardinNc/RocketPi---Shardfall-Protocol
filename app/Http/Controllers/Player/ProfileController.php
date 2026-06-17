<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Profile', [
            'user' => $request->user()->only([
                'id', 'name', 'email', 'display_name', 'slug', 'avatar_url',
            ]),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('Player/ProfilePublic', [
            'user' => array_merge(
                $user->only(['id', 'slug', 'name', 'display_name', 'avatar_url']),
                ['member_since' => $user->created_at?->toDateString()],
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'display_name')->ignore($request->user()->id),
            ],
            'avatar_url'   => 'nullable|url|max:255',
        ], [
            'display_name.unique' => 'Ce pseudo est déjà pris, choisis-en un autre.',
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profil mis à jour.');
    }
}
