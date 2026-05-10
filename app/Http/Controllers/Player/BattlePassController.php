<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BattlePassController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/BattlePass', [
            'season' => null,
            'tiers'  => [],
            'progress' => null,
        ]);
    }

    public function claim(Request $request, int $tier): RedirectResponse
    {
        // TODO Phase 3 — réclamation atomique
        return back()->with('status', "Palier {$tier} réclamé.");
    }
}
