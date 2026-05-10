<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\GachaPull;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminGachaLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'user'   => 'nullable|string|max:80',
            'banner' => 'nullable|integer',
            'rarity' => 'nullable|in:common,rare,epic,legendary',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
            'pity'   => 'nullable|in:any,hit,soft,rate_up',
        ]);

        $query = GachaPull::with([
            'user:id,name,email,display_name',
            'banner:id,name',
            'operator:id,name,codename,rarity,faction',
        ])->latest('created_at');

        if (! empty($filters['user'])) {
            $term = $filters['user'];
            $query->whereHas('user', fn ($q) => $q
                ->where('email', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%"));
        }
        if (! empty($filters['banner'])) {
            $query->where('banner_id', $filters['banner']);
        }
        if (! empty($filters['rarity'])) {
            $query->where('rarity', $filters['rarity']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        match ($filters['pity'] ?? 'any') {
            'hit'     => $query->where('was_pity_hit', true),
            'soft'    => $query->where('was_soft_pity', true),
            'rate_up' => $query->where('was_rate_up', true),
            default   => null,
        };

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/GachaLogs', [
            'logs'    => $logs,
            'filters' => $filters,
            'banners' => Banner::orderBy('name')->get(['id', 'name']),
            'stats'   => [
                'total'    => GachaPull::count(),
                'today'    => GachaPull::whereDate('created_at', today())->count(),
                'pity_hits'=> GachaPull::where('was_pity_hit', true)->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['user', 'banner', 'rarity', 'from', 'to']);

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'created_at', 'user_id', 'user_email', 'banner', 'operator_codename', 'rarity', 'cost', 'pity_before', 'pity_hit', 'soft_pity', 'rate_up', 'session_id', 'ip']);

            GachaPull::with(['user:id,email', 'banner:id,name', 'operator:id,codename'])
                ->when($filters['user']   ?? null, fn ($q, $u) => $q->whereHas('user', fn ($x) => $x->where('email', 'like', "%{$u}%")))
                ->when($filters['banner'] ?? null, fn ($q, $b) => $q->where('banner_id', $b))
                ->when($filters['rarity'] ?? null, fn ($q, $r) => $q->where('rarity', $r))
                ->when($filters['from']   ?? null, fn ($q, $f) => $q->whereDate('created_at', '>=', $f))
                ->when($filters['to']     ?? null, fn ($q, $t) => $q->whereDate('created_at', '<=', $t))
                ->orderBy('id')
                ->chunk(500, function ($pulls) use ($out) {
                    foreach ($pulls as $p) {
                        fputcsv($out, [
                            $p->id,
                            $p->created_at?->toIso8601String(),
                            $p->user_id,
                            $p->user?->email,
                            $p->banner?->name,
                            $p->operator?->codename,
                            $p->rarity,
                            $p->cost,
                            $p->pity_count_before,
                            $p->was_pity_hit ? '1' : '0',
                            $p->was_soft_pity ? '1' : '0',
                            $p->was_rate_up ? '1' : '0',
                            $p->session_id,
                            $p->ip_address,
                        ]);
                    }
                });

            fclose($out);
        }, 'gacha-logs-' . now()->format('Y-m-d') . '.csv');
    }
}
