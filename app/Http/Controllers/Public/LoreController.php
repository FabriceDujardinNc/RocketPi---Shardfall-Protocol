<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faction;
use App\Models\LeaderboardSeason;
use App\Models\Operator;
use App\Services\LeaderboardService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vitrines publiques (sans auth) indexables par Google.
 *
 * Expose le lore des factions, les fiches opérateurs (stats, lore, capacités)
 * sans aucune donnée joueur. Sert de socle SEO et de "demo" pour les visiteurs
 * curieux avant inscription.
 */
class LoreController extends Controller
{
    /**
     * Index lore : liste les 3 factions avec teasers.
     */
    public function index(): Response
    {
        $factions = Faction::query()
            ->orderBy('slug')
            ->get(['slug', 'name', 'tagline', 'lore', 'color_hue']);

        $operatorsCount = Operator::query()->where('is_available', true)->count();

        return Inertia::render('Public/Lore/Index', [
            'factions'       => $factions,
            'operatorsCount' => $operatorsCount,
        ]);
    }

    /**
     * Fiche faction publique avec son roster.
     */
    public function faction(Faction $faction): Response
    {
        $operators = $faction->operators()
            ->where('is_available', true)
            ->orderByRaw("CASE rarity WHEN 'legendary' THEN 1 WHEN 'epic' THEN 2 WHEN 'rare' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'codename', 'role', 'rarity', 'portrait_url']);

        return Inertia::render('Public/Lore/Faction', [
            'faction'   => $faction,
            'operators' => $operators,
        ]);
    }

    /**
     * Fiche opérateur publique : stats, capacités, lore (palier 0 uniquement).
     * Aucune donnée joueur : pas d'affinité, pas de possession, pas de constellation.
     */
    public function operator(Operator $operator): Response
    {
        abort_unless($operator->is_available, 404);

        return Inertia::render('Public/Lore/Operator', [
            'operator' => $operator->only([
                'slug', 'name', 'codename', 'faction', 'role', 'rarity',
                'lore', 'portrait_url',
                'stat_hp', 'stat_damage', 'stat_mobility',
                'weapon_name', 'weapon_description', 'abilities',
            ]),
        ]);
    }

    /**
     * Classement public en lecture seule (top 100 saison hebdo en cours).
     *
     * Page indexable — argument trafic SEO. N'expose que le pseudo, le rang
     * et le score (zéro PII), pas d'email ni de stats sensibles.
     */
    public function leaderboard(LeaderboardService $service): Response
    {
        // Saison hebdo active la plus récente (ce que voient les joueurs au quotidien).
        $season = LeaderboardSeason::query()
            ->where('type', 'weekly')
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderByDesc('starts_at')
            ->first();

        $entries = $season
            ? collect($service->topN($season, 100))
                ->map(fn ($e) => [
                    'rank'         => $e['rank'],
                    'display_name' => $e['display_name'] ?? $e['name'],
                    'score'        => $e['score'],
                ])
                ->values()
            : collect();

        return Inertia::render('Public/Leaderboard', [
            'season' => $season?->only(['id', 'name', 'type', 'season_number', 'starts_at', 'ends_at']),
            'entries' => $entries,
            'participantCount' => $season ? $service->participantCount($season) : 0,
        ]);
    }
}
