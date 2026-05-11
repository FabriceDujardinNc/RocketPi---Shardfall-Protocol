<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDailyLoginRewardRequest;
use App\Models\DailyLoginReward;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminDailyLoginRewardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', DailyLoginReward::class);

        return Inertia::render('Admin/DailyLoginRewards/Index', [
            'rewards' => DailyLoginReward::orderBy('day_number')->get(),
        ]);
    }

    public function store(StoreDailyLoginRewardRequest $request): RedirectResponse
    {
        DailyLoginReward::create($request->validated());
        return redirect()->route('admin.daily-login-rewards.index')
            ->with('status', 'Récompense ajoutée.');
    }

    public function update(StoreDailyLoginRewardRequest $request, DailyLoginReward $dailyLoginReward): RedirectResponse
    {
        $dailyLoginReward->update($request->validated());
        return redirect()->route('admin.daily-login-rewards.index')
            ->with('status', "Jour {$dailyLoginReward->day_number} mis à jour.");
    }

    public function destroy(DailyLoginReward $dailyLoginReward): RedirectResponse
    {
        $this->authorize('delete', $dailyLoginReward);
        $day = $dailyLoginReward->day_number;
        $dailyLoginReward->delete();
        return redirect()->route('admin.daily-login-rewards.index')
            ->with('status', "Jour {$day} supprimé. Le service retombera sur la récompense par défaut (100 credits).");
    }
}
