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
 *
 * La connexion n'est PAS obligatoire : un invité peut jouer. Dans ce cas Unity
 * reçoit un token vide et `user_id = 0`, ce qui désactive la sauvegarde de
 * progression côté serveur (le jeu reste pleinement jouable).
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
     * Config Unity WebGL. Si l'utilisateur est connecté, on émet un token
     * Sanctum éphémère (TTL 1h, révoqué au prochain load de /play, jamais
     * stocké en localStorage côté Unity). Pour un invité, le token est vide.
     */
    private function buildUnityConfig(Request $request): array
    {
        $user  = $request->user();
        $token = '';

        if ($user !== null) {
            PersonalAccessToken::where('tokenable_type', $user::class)
                ->where('tokenable_id', $user->id)
                ->where('name', 'unity-webgl')
                ->delete();

            $token = $user->createToken(
                name: 'unity-webgl',
                abilities: ['unity:*'],
                expiresAt: now()->addHour(),
            )->plainTextToken;
        }

        return [
            // Origine de la requête courante (et non config('app.url') figé) :
            // le site est servi sur plusieurs domaines (rocketpi.pro prod &
            // rocketpi-test.pro). Unity doit rappeler l'API sur le MÊME domaine
            // que celui où la page /play a été chargée — sinon CORS / mauvais env.
            'api_base_url' => $request->getSchemeAndHttpHost(),
            'api_token'    => $token,
            'user_id'      => $user?->id ?? 0,
            'locale'       => app()->getLocale(),
        ];
    }
}
