<?php

namespace App\Services;

use App\Models\User;

/**
 * Système d'XP / niveaux compte.
 *
 * Spec : niveaux 1 à 60+, croissance linéaire simple.
 * Formule : pour passer du niveau N au niveau N+1, il faut 100 × N XP.
 *
 * `account_xp` stocke l'XP cumulée DANS le niveau actuel (pas total).
 * `account_level` est plafonné à 99.
 *
 * Hooks au level-up : déclenche les paliers parrain (niv 5/15/30) via
 * ReferralService::checkLevelMilestones.
 */
class XpService
{
    public const MAX_LEVEL = 99;

    public function __construct(
        private readonly ReferralService $referrals,
        private readonly BattlePassService $battlePass,
    ) {}

    public function award(User $user, int $xp): array
    {
        if ($xp <= 0) {
            return ['xp_gained' => 0, 'leveled_up' => false, 'new_level' => $user->account_level];
        }

        $startLevel = $user->account_level;
        $user->account_xp += $xp;

        while ($user->account_level < self::MAX_LEVEL && $user->account_xp >= $this->thresholdFor($user->account_level)) {
            $user->account_xp -= $this->thresholdFor($user->account_level);
            $user->account_level++;
        }
        if ($user->account_level >= self::MAX_LEVEL) {
            $user->account_xp = 0;
        }
        $user->save();

        // Hook référral : paliers 5/15/30 du filleul → reward parrain
        if ($user->account_level > $startLevel) {
            try {
                $this->referrals->checkLevelMilestones($user, $startLevel, $user->account_level);
            } catch (\Throwable $e) {
                \Log::warning('Referral milestone check failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        // Hook battle pass : chaque XP gagnée alimente le BP actif
        try {
            $this->battlePass->addXp($user, $xp);
        } catch (\Throwable $e) {
            \Log::warning('Battle pass XP add failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        return [
            'xp_gained'  => $xp,
            'leveled_up' => $user->account_level > $startLevel,
            'new_level'  => $user->account_level,
        ];
    }

    public function thresholdFor(int $level): int
    {
        return 100 * max(1, $level);
    }
}
