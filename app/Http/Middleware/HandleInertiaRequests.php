<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'app' => [
                'name' => config('app.name'),
                'env'  => app()->environment(),
            ],

            // URL canonique du site — utilisée par <SEO> pour générer les
            // URLs absolues dans Open Graph / Twitter Cards / canonical.
            'baseUrl' => rtrim(config('app.url'), '/'),

            'auth' => [
                'user' => fn () => $request->user()
                    ? $request->user()->only([
                        'id', 'name', 'display_name', 'slug', 'email', 'role',
                        'avatar_url', 'account_level', 'account_xp', 'faction',
                    ])
                    : null,
            ],

            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error'  => fn () => $request->session()->get('error'),
            ],

            // État du quick login dev : `enabled` indique que la feature est
            // disponible (APP_ENV=local + DEV_LOGIN_PASSWORD défini) ; `unlocked`
            // indique que l'utilisateur a saisi le bon mot de passe dans cette
            // session. Tant que `unlocked` est faux, `users` reste à null pour
            // ne jamais exposer la liste des emails.
            'devLogin' => fn () => [
                'enabled'  => $this->devLoginEnabled($request),
                'unlocked' => $request->session()->get('dev_login.unlocked') === true,
            ],

            'devUsers' => fn () => $this->shouldExposeDevUsers($request)
                ? User::query()
                    ->orderByDesc('role')
                    ->orderBy('id')
                    ->get(['id', 'email', 'display_name', 'name', 'role', 'is_banned'])
                    ->map(fn (User $u) => [
                        'id'           => $u->id,
                        'email_masked' => self::maskEmail($u->email),
                        'display_name' => $u->display_name,
                        'name'         => $u->name,
                        'role'         => $u->role,
                        'is_banned'    => $u->is_banned,
                    ])
                    ->toArray()
                : null,
        ];
    }

    private function devLoginEnabled(Request $request): bool
    {
        if (blank(config('auth.dev_login.password'))) {
            return false;
        }

        $allowedHosts = (array) config('auth.dev_login.allowed_hosts', []);
        return in_array($request->getHost(), $allowedHosts, true);
    }

    private function shouldExposeDevUsers(Request $request): bool
    {
        return $this->devLoginEnabled($request)
            && $request->session()->get('dev_login.unlocked') === true;
    }

    /**
     * Masque l'email pour l'UI de quick login : `adm…@r…o`. La valeur réelle
     * n'est jamais exposée au front — on ne la retrouve que via l'id côté
     * controller au moment du POST /login.
     */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $maskedLocal = mb_strlen($local) > 3
            ? mb_substr($local, 0, 3) . '…'
            : ($local !== '' ? mb_substr($local, 0, 1) . '…' : '…');

        if ($domain === '') {
            return $maskedLocal;
        }

        $dot           = mb_strrpos($domain, '.');
        $domainHead    = $dot !== false ? mb_substr($domain, 0, 1) : mb_substr($domain, 0, 1);
        $domainTail    = $dot !== false ? mb_substr($domain, $dot) : '';

        return $maskedLocal . '@' . $domainHead . '…' . $domainTail;
    }
}
