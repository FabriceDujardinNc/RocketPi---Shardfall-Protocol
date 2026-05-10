<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'app' => [
                'name' => config('app.name'),
                'env'  => app()->environment(),
            ],

            'auth' => [
                'user' => fn () => $request->user()
                    ? $request->user()->only([
                        'id', 'name', 'display_name', 'slug', 'email', 'role',
                        'avatar_url', 'account_level', 'account_xp',
                    ])
                    : null,
            ],

            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error'  => fn () => $request->session()->get('error'),
            ],

            // Liste des comptes pour le quick login dev (uniquement local).
            // Lazy : recalculé à chaque requête, mais seulement consommé en dev.
            'devUsers' => fn () => app()->environment('local')
                ? User::query()
                    ->orderByDesc('role')
                    ->orderBy('id')
                    ->get(['id', 'email', 'display_name', 'name', 'role', 'is_banned'])
                    ->toArray()
                : null,
        ];
    }
}
