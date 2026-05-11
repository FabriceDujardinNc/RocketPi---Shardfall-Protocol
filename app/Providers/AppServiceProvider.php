<?php

namespace App\Providers;

use App\Models\Achievement;
use App\Models\Banner;
use App\Models\BattlePass;
use App\Models\Cosmetic;
use App\Models\DailyLoginReward;
use App\Models\Event;
use App\Models\Faction;
use App\Models\LeaderboardSeason;
use App\Models\Mission;
use App\Models\Operator;
use App\Models\ReferralReward;
use App\Models\Setting;
use App\Models\User;
use App\Policies\AchievementPolicy;
use App\Policies\BannerPolicy;
use App\Policies\BattlePassPolicy;
use App\Policies\CosmeticPolicy;
use App\Policies\DailyLoginRewardPolicy;
use App\Policies\EventPolicy;
use App\Policies\FactionPolicy;
use App\Policies\LeaderboardSeasonPolicy;
use App\Policies\MissionPolicy;
use App\Policies\OperatorPolicy;
use App\Policies\ReferralRewardPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ReferralReward::class, ReferralRewardPolicy::class);
        Gate::policy(Operator::class, OperatorPolicy::class);
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(Mission::class, MissionPolicy::class);
        Gate::policy(BattlePass::class, BattlePassPolicy::class);
        Gate::policy(DailyLoginReward::class, DailyLoginRewardPolicy::class);
        Gate::policy(Faction::class, FactionPolicy::class);
        Gate::policy(Achievement::class, AchievementPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(LeaderboardSeason::class, LeaderboardSeasonPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Cosmetic::class, CosmeticPolicy::class);

        // Le super_admin contourne toutes les vérifications applicatives.
        // Les Policies/Gates "ban" et "promote" gardent leurs propres règles
        // (un super_admin ne se ban pas et ne se rétrograde pas lui-même).
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() && ! in_array($ability, ['ban', 'promote'], true)) {
                return true;
            }
            return null;
        });

        Gate::define('access-admin',       fn (User $user) => $user->isAdmin());
        Gate::define('manage-content',     fn (User $user) => $user->isAdmin());   // operators, banners, missions
        Gate::define('view-gacha-logs',    fn (User $user) => $user->isAdmin());
        Gate::define('flag-referrals',     fn (User $user) => $user->isAdmin());
        Gate::define('reset-leaderboards', fn (User $user) => $user->isAdmin());
        Gate::define('change-roles',       fn (User $user) => $user->isSuperAdmin());
    }
}
