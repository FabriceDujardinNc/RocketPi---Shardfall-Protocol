<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Page /play — host le canvas Unity WebGL.
 *
 * Site simplifié : plus de classement compétitif ni d'historique de matchs.
 * On expose juste le canvas + un token Sanctum éphémère pour l'auth Unity.
 */
class PlayController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Player/Play', [
            'unityConfig' => $this->buildUnityConfig($request),
        ]);
    }

    /**
     * Token Sanctum éphémère (TTL 1h) pour Unity WebGL. Revoqué au prochain
     * load de /play. Jamais stocké en localStorage côté Unity.
     */
    private function buildUnityConfig(Request $request): array
    {
        $user = $request->user();

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
