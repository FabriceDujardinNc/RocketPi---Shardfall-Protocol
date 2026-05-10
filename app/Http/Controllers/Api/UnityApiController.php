<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unity WebGL ↔ serveur — communication jeu Phase 4.
 *
 * Pour l'instant : stubs 501 Not Implemented. La logique réelle
 * (validation autoritaire des résultats, détection d'anomalies, ELO)
 * arrive avec l'intégration Unity en Phase 4.
 */
class UnityApiController extends Controller
{
    public function startSession(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Phase 4 — pas encore implémenté.'], 501);
    }

    public function validateSession(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Phase 4 — pas encore implémenté.'], 501);
    }

    public function submitMatchResult(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Phase 4 — pas encore implémenté.'], 501);
    }

    public function submitScore(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Phase 4 — pas encore implémenté.'], 501);
    }
}
