<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Services\MissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    public function __construct(private readonly MissionService $missions) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Player/Missions', [
            'daily'  => $this->missions->listForUser($user, 'daily'),
            'weekly' => $this->missions->listForUser($user, 'weekly'),
        ]);
    }

    public function claim(Request $request, Mission $mission): RedirectResponse
    {
        try {
            $result = $this->missions->claim(
                user: $request->user(),
                mission: $mission,
                ipAddress: $request->ip(),
            );

            $msg = "Mission « {$mission->title} » réclamée. +{$mission->xp_reward} XP.";
            if ($result['xp']['leveled_up']) {
                $msg .= " 🎉 Niveau {$result['xp']['new_level']} !";
            }
            return back()->with('status', $msg);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['mission' => $e->getMessage()]);
        }
    }
}
