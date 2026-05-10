<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Leaderboard', [
            'season' => null,
            'topEntries' => [],
            'userRank' => null,
        ]);
    }

    public function show(Request $request, string $season): Response
    {
        return Inertia::render('Player/Leaderboard', [
            'season' => ['slug' => $season],
            'topEntries' => [],
            'userRank' => null,
        ]);
    }
}
