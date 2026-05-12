<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchSession;
use App\Services\LeaderboardService;
use App\Services\MatchService;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Unity WebGL ↔ serveur — communication jeu Phase 4.
 *
 * Authentification : middleware `auth:sanctum` (token signé délivré par
 * `AuthApiController::issueToken` via login API). Le client Unity stocke
 * le token dans son storage et l'envoie en header `Authorization: Bearer …`.
 *
 * Anti-cheat de base :
 *  - Throttle 30 matchs/heure sur submit (déjà sur la route)
 *  - MatchService valide durée/score/kills plausibles
 *  - Limite 50 matchs ranked/jour vérifiée à l'open de session
 *  - Session token SHA-256 unique, TTL 35min, single-use
 */
class UnityApiController extends Controller
{
    public function __construct(
        private readonly MatchService $matches,
        private readonly RankingService $ranking,
        private readonly LeaderboardService $leaderboard,
    ) {}

    /**
     * Démarre une session de match — renvoie session_token à passer à Unity.
     */
    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode'             => 'required|in:'.implode(',', MatchSession::MODES),
            'rank_type'        => 'required|in:'.implode(',', MatchSession::RANK_TYPES),
            'operator_used_id' => 'nullable|integer|exists:operators,id',
        ]);

        try {
            $result = $this->matches->start(
                user:               $request->user(),
                mode:               $validated['mode'],
                rankType:           $validated['rank_type'],
                operatorUsedId:     $validated['operator_used_id'] ?? null,
                clientIp:           $request->ip(),
                clientFingerprint:  $request->header('X-Device-Fingerprint'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $session = $result['session'];

        return response()->json([
            'session_token' => $result['session_token'],
            'session_id'    => $session->id,
            'mode'          => $session->mode,
            'rank_type'     => $session->rank_type,
            'started_at'    => $session->started_at->toIso8601String(),
            'ttl_seconds'   => 35 * 60,
        ]);
    }

    /**
     * Validation rapide d'une session côté Unity (heartbeat, etc.).
     */
    public function validateSession(Request $request): JsonResponse
    {
        $validated = $request->validate(['session_token' => 'required|string|size:64']);
        $session = MatchSession::where('session_token', $validated['session_token'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (! $session) {
            return response()->json(['valid' => false, 'error' => 'Session inconnue.'], 404);
        }
        return response()->json([
            'valid'      => $session->status === 'started',
            'status'     => $session->status,
            'started_at' => $session->started_at->toIso8601String(),
        ]);
    }

    /**
     * Soumet le résultat d'un match — finalise et calcule rank delta.
     */
    public function submitMatchResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_token'    => 'required|string|size:64',
            'score'            => 'required|integer|min:0|max:1000000',
            'kills'            => 'nullable|integer|min:0|max:10000',
            'deaths'           => 'nullable|integer|min:0|max:10000',
            'assists'          => 'nullable|integer|min:0|max:10000',
            'won'              => 'nullable|boolean',
            'is_mvp'           => 'nullable|boolean',
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
        ]);

        try {
            $result = $this->matches->finish(
                $validated['session_token'],
                $validated,
                $this->leaderboard,
            );
        } catch (RuntimeException $e) {
            MatchSession::where('session_token', $validated['session_token'])
                ->where('status', 'started')
                ->update(['status' => 'invalidated']);
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($result->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Session ne correspond pas à ton compte.'], 403);
        }

        $user = $request->user()->fresh();
        return response()->json([
            'result' => [
                'won'                => (bool) $result->won,
                'is_mvp'             => (bool) $result->is_mvp,
                'score'              => $result->score,
                'rank_points_delta'  => $result->rank_points_delta,
                'rank_points_after'  => $result->rank_points_after,
                'tier'               => $this->ranking->tierFor($result->rank_points_after),
            ],
            'user' => [
                'rank_points'           => $user->rank_points,
                'tier'                  => $this->ranking->tierFor($user->rank_points),
                'next_tier_threshold'   => $this->ranking->nextTierThreshold($user->rank_points),
                'daily_matches_played'  => $user->daily_matches_played,
                'daily_matches_limit'   => RankingService::DAILY_MATCH_LIMIT,
            ],
        ]);
    }

    /**
     * Endpoint legacy : soumet uniquement un score sans calcul de rank.
     * Délégué vers submitMatchResult avec won/is_mvp à false.
     */
    public function submitScore(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string|size:64',
            'score'         => 'required|integer|min:0|max:1000000',
        ]);
        return $this->submitMatchResult($request->merge(['won' => false, 'is_mvp' => false]));
    }
}
