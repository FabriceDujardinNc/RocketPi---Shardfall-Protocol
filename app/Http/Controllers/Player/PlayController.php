<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Page /play — host Unity WebGL + UX classement compétitif.
 *
 * Le canvas Unity sera intégré une fois le build Unity 6 livré (externe à
 * ce dépôt). Pour l'instant, la page sert de tableau de bord compétitif :
 * affiche le rank tier, les points, la limite journalière, l'historique
 * des 10 derniers matchs.
 */
class PlayController extends Controller
{
    public function __construct(private readonly RankingService $ranking) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $history = MatchResult::with('session:id,mode,rank_type,started_at,duration_seconds')
            ->where('user_id', $user->id)
            ->whereNotNull('validated_at')
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->map(fn (MatchResult $r) => [
                'id'                => $r->id,
                'mode'              => $r->session?->mode,
                'rank_type'         => $r->session?->rank_type,
                'started_at'        => $r->session?->started_at,
                'duration_seconds'  => $r->session?->duration_seconds,
                'score'             => $r->score,
                'kills'             => $r->kills,
                'deaths'            => $r->deaths,
                'assists'           => $r->assists,
                'won'               => (bool) $r->won,
                'is_mvp'            => (bool) $r->is_mvp,
                'rank_points_delta' => $r->rank_points_delta,
            ]);

        return Inertia::render('Player/Play', [
            'rank' => [
                'points'              => $user->rank_points,
                'tier'                => $this->ranking->tierFor($user->rank_points),
                'next_tier_threshold' => $this->ranking->nextTierThreshold($user->rank_points),
            ],
            'daily' => [
                'played' => $user->daily_matches_played ?? 0,
                'limit'  => RankingService::DAILY_MATCH_LIMIT,
            ],
            'history'     => $history,
            'photonAppId' => config('services.photon.app_id'),
            'unityConfig' => $this->buildUnityConfig($request),
        ]);
    }

    /**
     * Génère un token Sanctum éphémère (TTL 1h) pour le client Unity WebGL.
     * Le token est revoqué au prochain chargement de /play (cleanup) pour éviter
     * d'engorger personal_access_tokens. Sécurité : usage strict côté client
     * Unity, jamais stocké en localStorage (cf. unity-client/CLAUDE.md).
     */
    private function buildUnityConfig(Request $request): array
    {
        $user = $request->user();

        // Purge des anciens tokens unity-webgl du user pour éviter l'accumulation.
        PersonalAccessToken::where('tokenable_type', $user::class)
            ->where('tokenable_id', $user->id)
            ->where('name', 'unity-webgl')
            ->delete();

        $token = $user->createToken(
            name: 'unity-webgl',
            abilities: ['unity:*'],
            expiresAt: now()->addHour(),
        )->plainTextToken;

        return [
            'api_base_url' => rtrim(config('app.url'), '/'),
            'api_token'    => $token,
            'user_id'      => $user->id,
            'locale'       => app()->getLocale(),
        ];
    }
}
