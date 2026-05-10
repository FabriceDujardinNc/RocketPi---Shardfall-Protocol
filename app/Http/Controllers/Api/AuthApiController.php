<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Auth API pour Unity WebGL — émission de tokens Sanctum.
 * Phase 4 : intégration complète. Pour l'instant, endpoints fonctionnels
 * basiques (issue/revoke).
 */
class AuthApiController extends Controller
{
    public function issueToken(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device'   => 'nullable|string|max:64',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Identifiants invalides.'], 401);
        }
        if ($user->is_banned) {
            return response()->json(['error' => 'Compte banni.'], 403);
        }

        $token = $user->createToken($credentials['device'] ?? 'unity-webgl')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->only(['id', 'name', 'display_name', 'role', 'account_level']),
        ]);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'revoked']);
    }
}
