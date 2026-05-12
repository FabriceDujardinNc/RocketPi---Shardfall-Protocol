<?php

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the reverse proxy (Caddy) so Laravel reads the real client
        // IP and detects HTTPS via X-Forwarded-Proto. Without this, asset
        // URLs are generated with http:// even when the request came via
        // https://, which triggers mixed-content blocking in browsers.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\TrackUserActivity::class,
        ]);
        $middleware->alias([
            'admin'      => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'not.banned' => \App\Http\Middleware\EnsureUserIsNotBanned::class,
            '2fa'        => \App\Http\Middleware\EnsureTwoFactorPassed::class,
        ]);

        // Redirige les users déjà authentifiés qui visitent login/register
        // vers leur espace : admin → /admin, joueur → /dashboard.
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            return $user instanceof User && $user->isAdmin() ? '/admin' : '/dashboard';
        });

        // Redirige les guests vers /login (au lieu du défaut Laravel)
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
