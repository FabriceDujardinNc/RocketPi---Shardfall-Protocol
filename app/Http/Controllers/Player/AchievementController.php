<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    public function __construct(private readonly AchievementService $service) {}

    public function index(Request $request): Response
    {
        $list = $this->service->listForUser($request->user());

        // Group by category
        $byCategory = collect($list)->groupBy('category')->toArray();

        $stats = [
            'total'     => count($list),
            'completed' => count(array_filter($list, fn ($a) => $a['completed'])),
            'claimable' => count(array_filter($list, fn ($a) => $a['completed'] && ! $a['reward_claimed'])),
        ];

        return Inertia::render('Player/Achievements', [
            'achievements'   => $list,
            'byCategory'     => $byCategory,
            'stats'          => $stats,
        ]);
    }

    public function claim(Request $request, UserAchievement $userAchievement): RedirectResponse
    {
        try {
            $this->service->claim($request->user(), $userAchievement, $request->ip());
            return back()->with('status', 'Achievement réclamé.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['achievement' => $e->getMessage()]);
        }
    }
}
