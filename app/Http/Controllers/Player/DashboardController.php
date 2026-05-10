<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PlayerOperator;
use App\Services\DailyLoginService;
use App\Services\MissionService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DailyLoginService $dailyLogin,
        private readonly MissionService $missions,
        private readonly XpService $xp,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $shards = (int) (Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_SHARDS)
            ->value('balance') ?? 0);

        $credits = (int) (Currency::where('user_id', $user->id)
            ->where('type', Currency::TYPE_CREDITS)
            ->value('balance') ?? 0);

        $operatorsCount = PlayerOperator::where('user_id', $user->id)->count();

        $dailyMissions = $this->missions->listForUser($user, 'daily');
        $weeklyMissions = $this->missions->listForUser($user, 'weekly');

        // Marque la mission login auto-progresse à 1 (dashboard load)
        $this->missions->progressFor($user, 'login', 1);

        return Inertia::render('Player/Dashboard', [
            'user' => $user->only(['id', 'name', 'display_name', 'account_level', 'account_xp']),
            'xpThreshold' => $this->xp->thresholdFor($user->account_level),
            'currencies' => [
                'shards' => $shards,
                'credits' => $credits,
            ],
            'operatorsCount' => $operatorsCount,
            'dailyLogin' => $this->dailyLogin->todayStatus($user),
            'dailyMissions' => $dailyMissions,
            'weeklyMissions' => $weeklyMissions,
        ]);
    }
}
