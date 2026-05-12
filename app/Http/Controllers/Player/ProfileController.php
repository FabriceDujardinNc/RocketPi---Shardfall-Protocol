<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\LeaderboardSeason;
use App\Models\Operator;
use App\Models\OperatorAffinity;
use App\Models\PlayerOperator;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\LeaderboardService;
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
                'account_level', 'account_xp', 'referral_code',
            ]),
        ]);
    }

    public function show(Request $request, User $user, LeaderboardService $leaderboard): Response
    {
        $operatorTotal      = Operator::count();
        $operatorOwned      = PlayerOperator::where('user_id', $user->id)->count();
        $achievementTotal   = Achievement::where('is_hidden', false)->count();
        $achievementClaimed = UserAchievement::where('user_id', $user->id)
            ->where('completed', true)
            ->count();

        $topAffinities = OperatorAffinity::where('user_id', $user->id)
            ->with('operator:id,codename,name,faction,rarity')
            ->orderByDesc('level')
            ->orderByDesc('xp_current')
            ->take(4)
            ->get()
            ->map(fn ($a) => [
                'operator_codename' => $a->operator?->codename,
                'operator_name'     => $a->operator?->name,
                'faction'           => $a->operator?->faction,
                'rarity'            => $a->operator?->rarity,
                'level'             => $a->level,
            ])
            ->values();

        $ranks = LeaderboardSeason::where('is_active', true)
            ->get()
            ->map(function (LeaderboardSeason $season) use ($user, $leaderboard) {
                $rank  = $leaderboard->rankOf($user, $season);
                $score = $leaderboard->scoreOf($user, $season);
                if ($rank === null || $score <= 0) {
                    return null;
                }
                return [
                    'season' => $season->name,
                    'type'   => $season->type,
                    'rank'   => $rank + 1,
                    'score'  => $score,
                ];
            })
            ->filter()
            ->sortBy('rank')
            ->take(3)
            ->values();

        return Inertia::render('Player/ProfilePublic', [
            'user' => array_merge(
                $user->only(['id', 'slug', 'name', 'display_name', 'avatar_url', 'account_level', 'account_xp', 'faction']),
                ['member_since' => $user->created_at?->toDateString()],
            ),
            'stats' => [
                'operators_owned'    => $operatorOwned,
                'operators_total'    => $operatorTotal,
                'achievements_done'  => $achievementClaimed,
                'achievements_total' => $achievementTotal,
            ],
            'topAffinities' => $topAffinities,
            'ranks'         => $ranks,
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
