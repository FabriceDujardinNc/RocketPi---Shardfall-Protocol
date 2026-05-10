<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminGachaLogController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/GachaLogs', [
            'logs' => [],
            'filters' => $request->only(['user_id', 'banner', 'rarity', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // TODO Phase 2 — export CSV des logs gacha pour audit légal,
        // filtrable via $request (user_id, banner, rarity, from, to)
        $filters = $request->only(['user_id', 'banner', 'rarity', 'from', 'to']);

        return response()->streamDownload(function () use ($filters) {
            echo "user_id,banner,rarity,operator,timestamp\n";
            // TODO: stream les rows filtrées par $filters
        }, 'gacha-logs-' . now()->format('Y-m-d') . '.csv');
    }
}
