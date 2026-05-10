<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Dashboard', [
            'user' => $request->user()->only(['id', 'name', 'display_name', 'account_level', 'account_xp']),
        ]);
    }
}
