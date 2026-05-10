<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayController extends Controller
{
    public function index(Request $request): Response
    {
        // Phase 4 — embed Unity 6 WebGL avec token Sanctum signé
        return Inertia::render('Player/Play', [
            'sessionToken' => null,
            'photonAppId'  => config('services.photon.app_id'),
        ]);
    }
}
