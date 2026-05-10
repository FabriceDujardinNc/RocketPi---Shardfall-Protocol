<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Missions', [
            'daily' => [],
            'weekly' => [],
        ]);
    }

    public function claim(Request $request, int $mission): RedirectResponse
    {
        // TODO Phase 2 — réclamation atomique côté serveur
        return back()->with('status', "Mission {$mission} : récompense réclamée.");
    }
}
