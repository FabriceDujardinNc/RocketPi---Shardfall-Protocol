<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password',
    'display_name', 'slug', 'avatar_url',
    'role', 'account_level', 'account_xp',
    'faction',
    'rank_points', 'daily_matches_played', 'daily_matches_reset_at',
    'referral_code', 'referred_by_user_id',
    'last_active_at', 'is_banned', 'ban_reason', 'banned_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const FACTIONS = ['ORBIT', 'FERRO', 'VEIL'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_at' => 'datetime',
            'banned_at' => 'datetime',
            'is_banned' => 'boolean',
            'account_level' => 'integer',
            'account_xp' => 'integer',
            'rank_points' => 'integer',
            'daily_matches_played' => 'integer',
            'daily_matches_reset_at' => 'date',
            // 2FA TOTP — secret + recovery codes chiffrés en BDD, jamais exposés au front.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Pour les rôles admin / super_admin, la 2FA est obligatoire.
     * Le middleware bascule vers le setup tant que ce n'est pas confirmé.
     */
    public function requiresTwoFactor(): bool
    {
        return $this->isAdmin() && ! $this->hasTwoFactorEnabled();
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = self::generateUniqueReferralCode();
            }
        });

        // Faction immuable : une fois définie, on ne peut plus la changer.
        // Bloque toute tentative d'update (admin ou via Eloquent) — la seule
        // façon de l'écrire est lors du `create()` initial.
        static::updating(function (User $user) {
            if ($user->isDirty('faction') && $user->getOriginal('faction')) {
                throw new \RuntimeException(
                    'La faction est immuable une fois choisie (joueur '.$user->id.').'
                );
            }
        });

        // Generate / refresh URL slug whenever the source pseudo changes.
        // We use display_name primarily and fall back to name. Sharing a
        // profile after a rename will yield a fresh URL — old links break,
        // which matches the user's expectation that the URL reflects the
        // current pseudo.
        static::saving(function (User $user) {
            $needsSlug = empty($user->slug)
                || $user->isDirty('display_name')
                || ($user->isDirty('name') && empty($user->display_name));

            if ($needsSlug) {
                $base = Str::slug($user->display_name ?? $user->name) ?: 'user';
                $user->slug = self::makeUniqueSlug($base, $user->id);
            }
        });
    }

    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = sprintf(
                '%s-%s-%s',
                strtoupper(Str::random(3)),
                strtoupper(Str::random(4)),
                strtoupper(Str::random(4)),
            );
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public static function makeUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i    = 2;
        while (self::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }
        return $slug;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    /**
     * Faction d'allégeance du joueur (choisie à l'inscription, immuable).
     * Renvoie null pour les rares cas legacy non backfilled.
     */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'faction', 'slug');
    }
}
