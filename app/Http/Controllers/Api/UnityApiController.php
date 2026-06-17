<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchSession;
use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Unity WebGL ↔ serveur — communication jeu (mode training simplifié).
 *
 * Auth Sanctum, anti-cheat de base (durée/score/kills cap, single-use token).
 */
class UnityApiController extends Controller
{
    public function __construct(
        private readonly MatchService $matches,
    ) {}

    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:'.implode(',', MatchSession::MODES),
        ]);

        try {
            $result = $this->matches->start(
                user:              $request->user(),
                mode:              $validated['mode'],
                clientIp:          $request->ip(),
                clientFingerprint: $request->header('X-Device-Fingerprint'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $session = $result['session'];
        return response()->json([
            'session_token' => $result['session_token'],
            'session_id'    => $session->id,
            'mode'          => $session->mode,
            'started_at'    => $session->started_at->toIso8601String(),
            'ttl_seconds'   => 35 * 60,
        ]);
    }

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
            $result = $this->matches->finish($validated['session_token'], $validated);
        } catch (RuntimeException $e) {
            MatchSession::where('session_token', $validated['session_token'])
                ->where('status', 'started')
                ->update(['status' => 'invalidated']);
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($result->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Session ne correspond pas à ton compte.'], 403);
        }

        return response()->json([
            'result' => [
                'won'    => (bool) $result->won,
                'is_mvp' => (bool) $result->is_mvp,
                'score'  => $result->score,
            ],
        ]);
    }

    public function submitScore(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string|size:64',
            'score'         => 'required|integer|min:0|max:1000000',
        ]);
        return $this->submitMatchResult($request->merge(['won' => false, 'is_mvp' => false]));
    }
}
