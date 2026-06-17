<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force la policy "pas de trailing slash" sur les URLs.
 *
 * SEO : Google traite `/lore` et `/lore/` comme deux pages distinctes
 * (duplicate content). On uniformise via 301 vers la version sans slash.
 *
 * Exceptions :
 *  - Racine `/` (toujours conservée)
 *  - Méthodes non-GET (formulaires POST/PUT — pas de redirect possible
 *    sans perdre le body).
 */
class RedirectTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        // 301 uniquement sur GET/HEAD (POST/PUT/DELETE non redirigeables proprement)
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        // On regarde l'URI brute serveur (Symfony Request normalise sinon).
        $uri = $request->server('REQUEST_URI') ?? $request->getRequestUri();
        [$pathOnly, $query] = array_pad(explode('?', $uri, 2), 2, null);

        // Préserve la racine '/' et tout chemin sans trailing slash.
        if ($pathOnly === '/' || ! str_ends_with($pathOnly, '/')) {
            return $next($request);
        }

        $clean = rtrim($pathOnly, '/');
        $target = $query !== null ? "{$clean}?{$query}" : $clean;
        return redirect($target, 301);
    }
}
