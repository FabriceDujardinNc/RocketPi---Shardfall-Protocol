<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 2FA TOTP — setup et challenge à la connexion.
 *
 * Flow :
 *  - GET /2fa/setup       → écran QR + secret (stocké en session jusqu'à confirm)
 *  - POST /2fa/setup      → confirme avec code 6 chiffres, active, affiche recovery
 *  - GET /2fa/challenge   → écran de vérification post-login (si confirmed)
 *  - POST /2fa/challenge  → vérifie code TOTP ou recovery code
 *  - POST /2fa/disable    → désactive (super_admin only)
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $service) {}

    public function showSetup(Request $request): Response
    {
        $user = $request->user();

        // Réutilise le secret en session pour ne pas changer le QR à chaque refresh.
        $secret = $request->session()->get('2fa.pending_secret');
        if (! $secret) {
            $secret = $this->service->generateSecret();
            $request->session()->put('2fa.pending_secret', $secret);
        }

        $uri = $this->service->provisioningUri($user, $secret);

        return Inertia::render('Auth/TwoFactorSetup', [
            'secret'   => $secret,
            'qrSvg'    => $this->service->qrCodeSvg($uri),
            'required' => $user->requiresTwoFactor(),
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.size' => 'Le code TOTP fait exactement 6 chiffres.',
        ]);

        $secret = $request->session()->get('2fa.pending_secret');
        if (! $secret) {
            return redirect()->route('2fa.setup')->withErrors(['code' => 'Session expirée, recommence le setup.']);
        }

        try {
            $recovery = $this->service->confirm($request->user(), $secret, $validated['code']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $request->session()->forget('2fa.pending_secret');
        // Marque la session comme déjà passée par le challenge (pas redemandé après setup).
        $request->session()->put('2fa.passed', true);

        // Affiche les codes en clair UNE FOIS — il faut les sauvegarder maintenant.
        $request->session()->flash('2fa.recovery_codes', $recovery);

        return redirect()->route('2fa.recovery')->with('status', '2FA activée avec succès.');
    }

    public function showRecovery(Request $request): Response
    {
        $codes = $request->session()->get('2fa.recovery_codes', []);

        return Inertia::render('Auth/TwoFactorRecovery', [
            'codes' => $codes,
        ]);
    }

    public function showChallenge(): Response
    {
        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'     => 'nullable|string|size:6',
            'recovery' => 'nullable|string|max:32',
        ]);

        if (empty($validated['code']) && empty($validated['recovery'])) {
            return back()->withErrors(['code' => 'Fournis un code TOTP ou un code de secours.']);
        }

        $user = $request->user();
        $ok = ! empty($validated['code'])
            ? $this->service->verify($user, $validated['code'])
            : $this->service->consumeRecoveryCode($user, $validated['recovery'] ?? '');

        if (! $ok) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        $request->session()->put('2fa.passed', true);

        return redirect()->intended($user->isAdmin() ? '/admin' : '/dashboard');
    }

    public function disable(Request $request): RedirectResponse
    {
        // Seuls les comptes non-admin peuvent désactiver. Les admins doivent garder la 2FA.
        $user = $request->user();
        if ($user->isAdmin()) {
            return back()->withErrors(['2fa' => 'La 2FA est obligatoire pour les comptes admin.']);
        }

        $this->service->disable($user);
        return back()->with('status', '2FA désactivée.');
    }
}
