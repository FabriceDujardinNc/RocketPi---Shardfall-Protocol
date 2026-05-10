<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GachaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Gacha', [
            'banners' => [],
        ]);
    }

    public function show(Request $request, string $banner): Response
    {
        return Inertia::render('Player/GachaBanner', [
            'banner' => ['slug' => $banner],
        ]);
    }
}
