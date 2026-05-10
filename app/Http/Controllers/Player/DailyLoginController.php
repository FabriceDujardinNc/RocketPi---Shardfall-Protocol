<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Services\DailyLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DailyLoginController extends Controller
{
    public function __construct(private readonly DailyLoginService $service) {}

    public function claim(Request $request): RedirectResponse
    {
        try {
            $result = $this->service->claim($request->user(), $request->ip());

            $rewardSummary = collect($result['reward'])
                ->map(fn ($r) => "{$r['amount']} {$r['type']}")
                ->join(', ');

            return back()->with('status', "Récompense quotidienne réclamée (jour {$result['streak_day']}) : {$rewardSummary}.");
        } catch (\RuntimeException $e) {
            return back()->withErrors(['daily' => $e->getMessage()]);
        }
    }
}
