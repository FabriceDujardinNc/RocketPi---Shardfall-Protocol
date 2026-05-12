<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Gestion du second facteur TOTP (RFC 6238) pour les comptes admin.
 *
 * Compatible Google Authenticator, Authy, 1Password, Bitwarden, etc.
 * Le secret est stocké chiffré (cast encrypted sur User), les codes de
 * secours aussi. Aucun secret n'est jamais exposé au frontend ni loggué.
 */
class TwoFactorService
{
    private const RECOVERY_CODES_COUNT = 8;
    private const RECOVERY_CODE_LENGTH = 10;

    public function __construct(private readonly Google2FA $google2fa = new Google2FA()) {}

    /**
     * Génère un nouveau secret TOTP (32 chars base32). Pas encore "confirmé"
     * tant que `confirm()` n'a pas validé un code TOTP avec le secret.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * URI otpauth:// à encoder en QR code. Compatible apps standard.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = config('app.name');
        return $this->google2fa->getQRCodeUrl($issuer, $user->email, $secret);
    }

    /**
     * SVG inline d'un QR code pour le frontend (pas de dépendance externe à un proxy image).
     */
    public function qrCodeSvg(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd(),
        );
        return (new Writer($renderer))->writeString($uri);
    }

    /**
     * Vérifie un code TOTP 6 chiffres contre le secret stocké (avec window de
     * 1 étape ±30s pour tolérer une légère désync horloge).
     */
    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        return $this->google2fa->verifyKey($user->two_factor_secret, $code, 1);
    }

    /**
     * Vérifie un code de secours et le brûle (single-use). Renvoie true si
     * le code était valide et a été consommé.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $code = strtoupper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_filter($codes, fn ($c) => $c !== $code));
        $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
        return true;
    }

    /**
     * Génère 8 codes de secours (format XXXX-XXXX, 10 chars hex up).
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES_COUNT; $i++) {
            $raw = strtoupper(Str::random(self::RECOVERY_CODE_LENGTH));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }
        return $codes;
    }

    /**
     * Active définitivement la 2FA après vérification du premier code TOTP.
     * Génère et persiste les recovery codes. Renvoie les codes en clair (à
     * afficher UNE FOIS au joueur — non récupérables ensuite).
     *
     * @return array<int,string> recovery codes en clair
     */
    public function confirm(User $user, string $secret, string $code): array
    {
        if (! $this->google2fa->verifyKey($secret, $code, 1)) {
            throw new \RuntimeException('Code TOTP invalide.');
        }

        $recovery = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $recovery;
    }

    /**
     * Désactive complètement la 2FA — purge secret + recovery + confirmed_at.
     * Réservé aux cas où le joueur veut repartir de zéro (rotation app).
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
