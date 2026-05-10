<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminLeaderboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Leaderboards/Index', ['seasons' => []]);
    }

    public function show(Request $request, int $season): Response
    {
        return Inertia::render('Admin/Leaderboards/Show', [
            'seasonId' => $season,
            'entries' => [],
        ]);
    }

    public function reset(Request $request, int $season): RedirectResponse
    {
        return back()->with('status', "Saison {$season} réinitialisée.");
    }
}
