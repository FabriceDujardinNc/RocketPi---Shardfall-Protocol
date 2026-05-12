<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vue joueur des événements limités (Phase 4).
 *
 * Affiche les events actifs avec leur phase (current vs scheduled vs expired)
 * et les rewards associées. Indexable SEO (page publique, mais protégée par
 * auth dans la pratique car contenu motivant à la connexion).
 */
class EventController extends Controller
{
    public function index(): Response
    {
        $now = now();
        // Carbon est mutable : on garde une copie pour le filtre 'ends_at >= now-1w'
        // sans muter $now utilisé ensuite pour le calcul de phase.
        $oneWeekAgo = $now->copy()->subWeek();

        $events = Event::query()
            ->where('is_active', true)
            ->where(function ($q) use ($oneWeekAgo) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $oneWeekAgo);
            })
            ->with('banner:id,slug,name')
            ->orderByRaw('CASE WHEN starts_at > ? THEN 2 WHEN ends_at < ? THEN 3 ELSE 1 END', [$now, $now])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Event $e) => [
                'id'           => $e->id,
                'slug'         => $e->slug,
                'name'         => $e->name,
                'type'         => $e->type,
                'description'  => $e->description,
                'image_url'    => $e->image_url,
                'starts_at'    => $e->starts_at,
                'ends_at'      => $e->ends_at,
                'rewards_pool' => $e->rewards_pool,
                'phase'        => $e->starts_at?->gt($now) ? 'scheduled'
                    : ($e->ends_at?->lt($now) ? 'expired' : 'current'),
                'banner'       => $e->banner ? [
                    'slug' => $e->banner->slug,
                    'name' => $e->banner->name,
                ] : null,
            ]);

        return Inertia::render('Player/Events', [
            'events' => $events,
            'counts' => [
                'current'   => $events->where('phase', 'current')->count(),
                'scheduled' => $events->where('phase', 'scheduled')->count(),
                'expired'   => $events->where('phase', 'expired')->count(),
            ],
        ]);
    }
}
