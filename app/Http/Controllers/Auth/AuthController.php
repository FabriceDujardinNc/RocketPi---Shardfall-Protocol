<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    // ── Login ───────────────────────────────────────────────────────────

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Dev quick login : host whitelisté + DEV_LOGIN_PASSWORD défini + flag
        // `dev` → connexion sans password. Aucun effet sur les autres domaines
        // (check côté serveur via Host). Requiert aussi que la session ait été
        // déverrouillée via /dev-login/unlock. Le front envoie un `user_id`
        // (pas l'email — masqué côté UI) pour cibler le compte.
        $isDevQuick = $this->devLoginEnabled($request)
            && $request->boolean('dev')
            && $request->session()->get('dev_login.unlocked') === true;

        if ($isDevQuick) {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $user = User::find($validated['user_id']);
            Auth::login($user, true);
            $request->session()->regenerate();
            // Le déverrouillage est consommé par la régénération de session ; on le
            // remet en place pour rester déverrouillé après ce login, et on bypass
            // explicitement la 2FA (setup + challenge) pour faciliter les tests.
            $request->session()->put('dev_login.unlocked', true);
            $request->session()->put('2fa.passed', true);
            $request->session()->put('2fa.bypass', true);

            return redirect($user->isAdmin() ? route('admin.dashboard') : route('dashboard'))
                ->with('status', "Connecté en mode dev (user #{$user->id}).");
        }

        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($validated, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Identifiants invalides.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Déverrouille l'UI de quick login en validant le mot de passe partagé
     * (`DEV_LOGIN_PASSWORD`). Inerte si APP_ENV != local ou si la config est
     * vide — renvoie 404 pour ne pas signaler la présence de l'endpoint.
     */
    public function unlockDevLogin(Request $request): RedirectResponse
    {
        abort_unless($this->devLoginEnabled($request), 404);

        $validated = $request->validate([
            'dev_password' => 'required|string',
        ]);

        if (! hash_equals((string) config('auth.dev_login.password'), $validated['dev_password'])) {
            return back()->withErrors(['dev_password' => 'Mot de passe incorrect.']);
        }

        $request->session()->put('dev_login.unlocked', true);

        return back()->with('status', 'Quick login déverrouillé.');
    }

    private function devLoginEnabled(Request $request): bool
    {
        // La feature est gatée par le host de la requête (whitelist) + la
        // présence d'un DEV_LOGIN_PASSWORD non vide. Le backend rocketpi.pro
        // et rocketpi-test.pro partagent le même Laravel : on évite ainsi
        // d'activer la feature sur le domaine de prod par erreur.
        if (blank(config('auth.dev_login.password'))) {
            return false;
        }

        $allowedHosts = (array) config('auth.dev_login.allowed_hosts', []);
        return in_array($request->getHost(), $allowedHosts, true);
    }

    // ── Register ────────────────────────────────────────────────────────

    public function showRegister(Request $request): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:80|unique:users,name',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'name.unique'  => 'Ce pseudo est déjà pris, choisis-en un autre.',
            'email.unique' => 'Un compte existe déjà avec cet email.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ── Password reset ──────────────────────────────────────────────────

    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // ── Email verification ──────────────────────────────────────────────

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        return redirect()->route('play');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
