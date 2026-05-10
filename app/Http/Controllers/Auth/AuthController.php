<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReferralService;
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
    public function __construct(private readonly ReferralService $referrals) {}

    // ── Login ───────────────────────────────────────────────────────────

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Dev quick login : APP_ENV=local + flag `dev` → connexion sans password.
        // Aucun effet en prod (env check côté serveur).
        $isDevQuick = app()->environment('local') && $request->boolean('dev');

        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => $isDevQuick ? 'nullable|string' : 'required|string',
        ]);

        if ($isDevQuick) {
            $user = User::where('email', $validated['email'])->first();
            if (! $user) {
                return back()->withErrors(['email' => 'Aucun compte avec cet email.'])->onlyInput('email');
            }
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect($user->isAdmin() ? route('admin.dashboard') : route('dashboard'))
                ->with('status', "Connecté en tant que {$user->email} (dev mode).");
        }

        if (! Auth::attempt($validated, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Identifiants invalides.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ── Register ────────────────────────────────────────────────────────

    public function showRegister(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'referralCode' => $request->session()->get('referral_code'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:80|unique:users,name',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'referral_code' => 'nullable|string|max:32',
        ], [
            'name.unique'  => 'Ce pseudo est déjà pris, choisis-en un autre.',
            'email.unique' => 'Un compte existe déjà avec cet email.',
        ]);

        $referrer = ! empty($validated['referral_code'])
            ? User::where('referral_code', $validated['referral_code'])->first()
            : null;

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'referred_by_user_id' => $referrer?->id,
        ]);

        if ($referrer) {
            try {
                $this->referrals->createForNewUser(
                    referrer: $referrer,
                    referee: $user,
                    ip: $request->ip(),
                    fingerprint: $request->header('X-Device-Fingerprint'),
                );
            } catch (\RuntimeException $e) {
                \Log::info("Referral creation skipped for user {$user->id}: {$e->getMessage()}");
            }
        }

        event(new Registered($user));
        Auth::login($user);

        $request->session()->forget('referral_code');

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

        if ($user = $request->user()) {
            $this->referrals->validateOnEmailVerified($user);
        }

        return redirect()->route('dashboard');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
