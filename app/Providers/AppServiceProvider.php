<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No-op : tous les bindings métier ont été retirés avec la refonte
        // « site simplifié ». Le générateur 3D Meshy et les services gacha
        // ne sont plus nécessaires.
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        // Le super_admin contourne toutes les vérifications applicatives.
        // Les Policies/Gates "ban" et "promote" gardent leurs propres règles
        // (un super_admin ne se ban pas et ne se rétrograde pas lui-même).
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() && ! in_array($ability, ['ban', 'promote'], true)) {
                return true;
            }
            return null;
        });

        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
        Gate::define('change-roles', fn (User $user) => $user->isSuperAdmin());
    }
}
