<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde-fou 2FA pour les routes sensibles.
 *
 * Comportement :
 *  - Si l'utilisateur a la 2FA activée (`two_factor_confirmed_at` non null)
 *    mais n'a pas encore passé le challenge dans la session courante
 *    (session.2fa.passed != true) → redirige vers /2fa/challenge.
 *  - Si l'utilisateur est admin sans 2FA activée → redirige vers /2fa/setup
 *    (obligatoire pour les comptes admin).
 *
 * Monté sur le groupe admin pour bloquer toute action tant que 2FA pas
 * complétée. Les routes du flow 2FA elles-mêmes sont exemptes (la liste
 * d'exemptions est gérée en interne).
 */
class EnsureTwoFactorPassed
{
    private const EXEMPT_PATHS = [
        '2fa/setup',
        '2fa/recovery',
        '2fa/challenge',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Exempt les routes du flow 2FA pour éviter une boucle de redirection.
        foreach (self::EXEMPT_PATHS as $exempt) {
            if ($request->is($exempt)) {
                return $next($request);
            }
        }

        // Bypass intégral quand la session a été ouverte via quick login dev
        // sur un host whitelisté (cf. auth.dev_login.allowed_hosts). Permet de
        // tester /admin sans configurer un TOTP sur chaque compte de seed.
        // Sur les hosts non-whitelistés (ex. rocketpi.pro prod), le flag session
        // existe peut-être mais ce check refuse de l'honorer.
        if ($request->session()->get('2fa.bypass') === true
            && in_array($request->getHost(), (array) config('auth.dev_login.allowed_hosts', []), true)
        ) {
            return $next($request);
        }

        // Admin sans 2FA configurée → force le setup.
        if ($user->requiresTwoFactor()) {
            return redirect()->route('2fa.setup');
        }

        // 2FA configurée mais pas validée dans cette session → challenge.
        if ($user->hasTwoFactorEnabled() && ! $request->session()->get('2fa.passed')) {
            return redirect()->route('2fa.challenge');
        }

        return $next($request);
    }
}
