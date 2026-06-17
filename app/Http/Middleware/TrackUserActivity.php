<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tient à jour `users.last_active_at` quand l'utilisateur est connecté.
 *
 * Débounce de 5 minutes pour éviter un UPDATE par requête : si la valeur
 * stockée est récente, on ne touche pas la BDD. Sert notamment au gate
 * anti-fraude parrainage (`ReferralService::promoteIfActiveEnough`).
 */
class TrackUserActivity
{
    private const THROTTLE_SECONDS = 300; // 5 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $stale = ! $user->last_active_at
                || $user->last_active_at->lt(now()->subSeconds(self::THROTTLE_SECONDS));

            if ($stale) {
                $user->forceFill(['last_active_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
